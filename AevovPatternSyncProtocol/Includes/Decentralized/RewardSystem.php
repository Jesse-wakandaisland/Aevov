<?php

namespace Aevov\Decentralized;

class RewardSystem
{
    /**
     * The reward amount.
     *
     * @var float
     */
    private $rewardAmount;

    /**
     * Constructor.
     *
     * @param float $rewardAmount
     */
    public function __construct(float $rewardAmount = 1.0)
    {
        $this->rewardAmount = $rewardAmount;
    }

    /**
     * Rewards a contributor.
     *
     * @param Contributor $contributor
     */
    public function reward(Contributor $contributor): void
    {
        // In a real implementation, this would interact with a wallet or a balance sheet.
        // For now, we will just log the reward.
        error_log("Rewarding contributor {$contributor->getId()} with {$this->rewardAmount}");
    }
}
