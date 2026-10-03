<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Controller;
use App\VotingSystem\Core\VoteBlockchain;
use App\VotingSystem\Models\Election;

class HomeController extends Controller
{
    public function index(): void
    {
        $election = (new Election())->current();
        $electionId = (int) ($election['id'] ?? 1);

        // Show only public network metadata on the landing page. Ballot choices,
        // voter identity, and receipt details must never be exposed here.
        $blockchainStatus = [
            'chain_name' => 'OrgChain public ledger',
            'election_id' => $electionId,
            'node_count' => 0,
            'total_sealed_blocks' => 0,
            'latest_block_hash' => null,
            'consensus_algorithm' => 'Unavailable',
            'chain_id' => null,
            'status' => 'Unavailable',
            'nodes_health' => [
                'rpc' => 'offline',
                'qbft_validators' => 0,
                'peers' => 0,
                'block_number' => null,
            ],
        ];
        $blockchainHashes = [];

        try {
            $blockchain = new VoteBlockchain();
            $blockchainStatus = array_replace_recursive(
                $blockchainStatus,
                $blockchain->getChainStatus($electionId)
            );
            $blockchainHashes = $blockchain->publicHashHistory($electionId);
        } catch (\Throwable) {
            // Keep the voting entry point available if Besu is restarting.
        }

        $this->view('home/index', [
            'title' => 'OrgChain Official Voting',
            'election' => $election,
            'blockchainStatus' => $blockchainStatus,
            'blockchainHashes' => $blockchainHashes,
        ]);
    }
}
