<?php

namespace APS\API\Endpoints;

class ProofOfContributionEndpoint extends \WP_REST_Controller
{
    /**
     * The Proof of Contribution instance.
     *
     * @var \Aevov\Decentralized\ProofOfContribution
     */
    private $poc;

    /**
     * Constructor.
     *
     * @param \Aevov\Decentralized\ProofOfContribution $poc
     */
    public function __construct(\Aevov\Decentralized\ProofOfContribution $poc)
    {
        $this->poc = $poc;
        $this->namespace = 'aps/v1';
        $this->rest_base = 'poc';
    }

    /**
     * Registers the routes for the objects of the controller.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/mine', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'mine'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/chain', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'chain'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/nodes/register', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'register_nodes'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/nodes/resolve', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'resolve_nodes'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);
    }

    /**
     * Resolves conflicts between nodes.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function resolve_nodes(\WP_REST_Request $request)
    {
        $replaced = $this->poc->resolveConflicts();

        if ($replaced) {
            $response = [
                'message' => 'Our chain was replaced',
                'new_chain' => $this->poc->ledger->chain,
            ];
        } else {
            $response = [
                'message' => 'Our chain is authoritative',
                'chain' => $this->poc->ledger->chain,
            ];
        }

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Registers new nodes.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function register_nodes(\WP_REST_Request $request)
    {
        $nodes = $request->get_param('nodes');

        if (!is_array($nodes)) {
            return new \WP_REST_Response(['message' => 'Error: Please supply a valid list of nodes'], 400);
        }

        foreach ($nodes as $node) {
            $this->poc->registerNode($node);
        }

        $response = [
            'message' => 'New nodes have been added',
            'total_nodes' => count($this->poc->ledger->nodes),
        ];

        return new \WP_REST_Response($response, 201);
    }

    /**
     * Returns the full blockchain.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function chain(\WP_REST_Request $request)
    {
        $response = [
            'chain' => $this->poc->ledger->chain,
            'length' => count($this->poc->ledger->chain),
        ];

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Mines a new block.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function mine(\WP_REST_Request $request)
    {
        // We run the proof of work algorithm to get the next proof...
        $last_block = $this->poc->ledger->lastBlock();
        $last_proof = $last_block['proof'];
        $proof = $this->poc->consensus->proofOfWork($last_proof);

        // We must receive a reward for finding the proof.
        // The sender is "0" to signify that this node has mined a new coin.
        $this->poc->ledger->newTransaction(
            '0',
            $this->poc->node_identifier,
            1
        );

        // Forge the new Block by adding it to the chain
        $previous_hash = $this->poc->ledger->hash($last_block);
        $block = $this->poc->ledger->newBlock($proof, $previous_hash);

        $response = [
            'message' => 'New Block Forged',
            'index' => $block['index'],
            'transactions' => $block['transactions'],
            'proof' => $block['proof'],
            'previous_hash' => $block['previous_hash'],
        ];

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Checks if the user has permission to access the endpoint.
     *
     * @return bool
     */
    public function check_permission()
    {
        return current_user_can('manage_options');
    }
}
