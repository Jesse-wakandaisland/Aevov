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
     * Constructor.
     */
    public function __construct()
    {
        $this->chain = [];
        $this->currentTransactions = [];

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
}
