<?php

namespace Aevov\Decentralized;

class ProofOfContribution
{
    /**
     * The distributed ledger.
     *
     * @var DistributedLedger
     */
    private $ledger;

    /**
     * The consensus mechanism.
     *
     * @var ConsensusMechanism
     */
    private $consensus;

    /**
     * The reward system.
     *
     * @var RewardSystem
     */
    private $rewards;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ledger = new DistributedLedger();
        $this->consensus = new ConsensusMechanism(1);
        $this->rewards = new RewardSystem();
    }

    /**
     * Submits a contribution to the network.
     *
     * @param Contribution $contribution
     *
     * @return bool
     */
    public function submitContribution(Contribution $contribution): bool
    {
        // In a real implementation, we would validate the contribution before adding it to the transaction list.
        $this->ledger->newTransaction(
            $contribution->getContributor()->getId(),
            'network',
            1.0
        );

        // Mine a new block.
        $lastBlock = $this->ledger->lastBlock();
        $lastProof = $lastBlock['proof'];
        $proof = $this->consensus->proofOfWork($lastProof);

        // Forge the new Block by adding it to the chain.
        $previousHash = $this->ledger->hash($lastBlock);
        $this->ledger->newBlock($proof, $previousHash);

        // Reward the contributor.
        $this->rewards->reward($contribution->getContributor());

        return true;
    }
}
