<?php
/**
 * Pattern-related API endpoints
 * 
 * @package APS
 * @subpackage API\Endpoints
 */

namespace APS\API\Endpoints;

use APS\Analysis\PatternAnalyzer;
use APS\Network\PatternDistributor;

class PatternEndpoint extends BaseEndpoint {
    protected $base = 'patterns';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_patterns'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_collection_params()
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_pattern'],
                'permission_callback' => [$this, 'check_write_permission'],
                'args' => $this->get_endpoint_args_for_item_schema(true)
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->base . '/(?P<hash>[a-zA-Z0-9]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_pattern'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'hash' => [
                        'required' => true,
                        'type' => 'string',
                        'validate_callback' => [$this, 'validate_hash']
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_pattern'],
                'permission_callback' => [$this, 'check_write_permission']
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_pattern'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        // Pattern analysis endpoint
        register_rest_route($this->namespace, '/' . $this->base . '/analyze', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'analyze_pattern'],
                'permission_callback' => [$this, 'check_write_permission'],
                'args' => $this->get_analysis_args()
            ]
        ]);

        // Pattern distribution endpoint
        register_rest_route($this->namespace, '/' . $this->base . '/distribute/(?P<hash>[a-zA-Z0-9]+)', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'distribute_pattern'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);
    }

    public function get_patterns($request) {
        $args = [
            'type' => $request['type'] ?? null,
            'confidence' => $request['confidence'] ?? null,
            'page' => $request['page'] ?? 1,
            'per_page' => min($request['per_page'] ?? 10, 100),
            'orderby' => $request['orderby'] ?? 'created_at',
            'order' => $request['order'] ?? 'DESC'
        ];

        $patterns = $this->get_pattern_collection($args);
        $total = $this->get_patterns_count($args);

        $response = rest_ensure_response($patterns);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $args['per_page']));

        return $response;
    }

    public function create_pattern($request) {
        $pattern_data = $this->prepare_pattern_for_database($request);
        
        try {
            $pattern_id = $this->store_pattern($pattern_data);
            $pattern = $this->get_pattern_by_id($pattern_id);
            
            return rest_ensure_response($pattern);
        } catch (\Exception $e) {
            return new \WP_Error(
                'pattern_creation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_pattern($request) {
        $pattern = $this->get_pattern_by_hash($request['hash']);
        
        if (!$pattern) {
            return new \WP_Error(
                'pattern_not_found',
                'Pattern not found',
                ['status' => 404]
            );
        }

        return rest_ensure_response($pattern);
    }

    public function analyze_pattern($request) {
        $analyzer = new PatternAnalyzer();
        
        try {
            $analysis = $analyzer->analyze_pattern($request->get_params());
            return rest_ensure_response($analysis);
        } catch (\Exception $e) {
            return new \WP_Error(
                'analysis_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function distribute_pattern($request) {
        if (!is_multisite()) {
            return new \WP_Error(
                'distribution_error',
                'Pattern distribution requires multisite',
                ['status' => 400]
            );
        }

        $distributor = new PatternDistributor();
        
        try {
            $pattern = $this->get_pattern_by_hash($request['hash']);
            if (!$pattern) {
                return new \WP_Error(
                    'pattern_not_found',
                    'Pattern not found',
                    ['status' => 404]
                );
            }

            $distribution = $distributor->distribute_pattern($pattern);
            return rest_ensure_response($distribution);
        } catch (\Exception $e) {
            return new \WP_Error(
                'distribution_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    protected function get_collection_params() {
        return [
            'page' => [
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
            'per_page' => [
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'type' => [
                'description' => 'Filter patterns by type.',
                'type' => 'string',
            ],
            'confidence' => [
                'description' => 'Filter patterns by minimum confidence score.',
                'type' => 'number',
                'minimum' => 0,
                'maximum' => 1,
            ]
        ];
    }

    protected function get_analysis_args() {
        return [
            'data' => [
                'required' => true,
                'type' => 'object'
            ],
            'type' => [
                'required' => true,
                'type' => 'string'
            ],
            'options' => [
                'type' => 'object'
            ]
        ];
    }

    private function validate_hash($hash) {
        return preg_match('/^[a-zA-Z0-9]+$/', $hash);
    }
}