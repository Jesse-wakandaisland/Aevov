<?php

namespace Aevov\Decentralized;

class ConsensusMechanism
{
    /**
     * The last proof.
     *
     * @var int
     */
    private $lastProof;

    /**
     * Constructor.
     *
     * @param int $lastProof
     */
    public function __construct(int $lastProof)
    {
        $this->lastProof = $lastProof;
    }

    /**
     * Simple Proof of Work Algorithm:
     * - Find a number 'p' such that hash(pp') contains leading 4 zeroes, where p is the previous proof, and p' is the new proof.
     *
     * @param int $lastProof
     *
     * @return int
     */
    public function proofOfWork(int $lastProof): int
    {
        $proof = 0;
        while ($this->validProof($lastProof, $proof) === false) {
            $proof++;
        }

        return $proof;
    }

    /**
     * Validates the proof: Does hash(last_proof, proof) contain 4 leading zeroes?
     *
     * @param int $lastProof
     * @param int $proof
     *
     * @return bool
     */
    public static function validProof(int $lastProof, int $proof): bool
    {
        $guess = "{$lastProof}{$proof}";
        $guessHash = hash('sha256', $guess);

        return substr($guessHash, 0, 4) === '0000';
    }
}
