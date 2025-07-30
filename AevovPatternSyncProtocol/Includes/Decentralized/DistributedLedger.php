<?php

namespace Aevov\Decentralized;

class DistributedLedger
{
    /**
     * The chain of blocks.
     *
     * @var array
     */
    private $chain;

    /**
     * The current transactions.
     *
     * @var array
     */
    private $currentTransactions;

    /**
     * The list of nodes in the network.
     *
     * @var array
     */
    private $nodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->chain = [];
        $this->currentTransactions = [];
        $this->nodes = [];

        // Create the genesis block.
        $this->newBlock(100, 1);
    }

    /**
     * Creates a new block and adds it to the chain.
     *
     * @param int $proof
     * @param int $previousHash
     *
     * @return array
     */
    public function newBlock(int $proof, int $previousHash): array
    {
        $block = [
            'index' => count($this->chain) + 1,
            'timestamp' => time(),
            'transactions' => $this->currentTransactions,
            'proof' => $proof,
            'previous_hash' => $previousHash,
        ];

        // Reset the current list of transactions.
        $this->currentTransactions = [];

        $this->chain[] = $block;

        return $block;
    }

    /**
     * Adds a new transaction to the list of transactions.
     *
     * @param string $sender
     * @param string $recipient
     * @param float  $amount
     *
     * @return int
     */
    public function newTransaction(string $sender, string $recipient, float $amount): int
    {
        $this->currentTransactions[] = [
            'sender' => $sender,
            'recipient' => $recipient,
            'amount' => $amount,
        ];

        return $this->lastBlock()['index'] + 1;
    }

    /**
     * Returns the last block in the chain.
     *
     * @return array
     */
    public function lastBlock(): array
    {
        return end($this->chain);
    }

    /**
     * Hashes a block.
     *
     * @param array $block
     *
     * @return string
     */
    public static function hash(array $block): string
    {
        // We must make sure that the Dictionary is Ordered, or we'll have inconsistent hashes
        $blockString = json_encode($block, SORT_REGULAR);

        return hash('sha256', $blockString);
    }

    /**
     * Registers a new node.
     *
     * @param string $address
     */
    public function registerNode(string $address): void
    {
        $parsedUrl = parse_url($address);
        $this->nodes[] = $parsedUrl['host'];
    }

    /**
     * Determines if a given blockchain is valid.
     *
     * @param array $chain
     *
     * @return bool
     */
    public function validChain(array $chain): bool
    {
        $lastBlock = $chain[0];
        $currentIndex = 1;

        while ($currentIndex < count($chain)) {
            $block = $chain[$currentIndex];
            // Check that the hash of the block is correct
            if ($block['previous_hash'] !== self::hash($lastBlock)) {
                return false;
            }

            // Check that the Proof of Work is correct
            if (!ConsensusMechanism::validProof($lastBlock['proof'], $block['proof'])) {
                return false;
            }

            $lastBlock = $block;
            $currentIndex++;
        }

        return true;
    }

    /**
     * This is our consensus algorithm, it resolves conflicts by replacing our chain with the longest one in the network.
     *
     * @return bool
     */
    public function resolveConflicts(): bool
    {
        $neighbours = $this->nodes;
        $newChain = null;

        // We're only looking for chains longer than ours
        $maxLength = count($this->chain);

        // Grab and verify the chains from all the nodes in our network
        foreach ($neighbours as $node) {
            $response = file_get_contents("http://{$node}/chain");

            if ($response) {
                $length = json_decode($response, true)['length'];
                $chain = json_decode($response, true)['chain'];

                // Check if the length is longer and the chain is valid
                if ($length > $maxLength && $this->validChain($chain)) {
                    $maxLength = $length;
                    $newChain = $chain;
                }
            }
        }

        // Replace our chain if we discovered a new, valid chain longer than ours
        if ($newChain) {
            $this->chain = $newChain;
            return true;
        }

        return false;
    }
}
