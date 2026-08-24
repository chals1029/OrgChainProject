<?php

namespace App\VotingSystem\Controllers;

use App\VotingSystem\Core\Controller;
use App\VotingSystem\Core\VoteBlockchain;

class ApiController extends Controller
{
    public function verify(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $reference = trim($_GET['reference'] ?? $_GET['ref'] ?? '');
        if ($reference === '') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing parameter: reference is required. Example: ?reference=DEMO-REF-1234',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $blockchain = new VoteBlockchain();
        $result = $blockchain->verify($reference);

        if (! $result['ok'] && isset($result['message']) && $result['message'] === 'Receipt not found in the voting database.') {
            http_response_code(404);
        } else {
            http_response_code(200);
        }

        echo json_encode([
            'api_version' => '1.0',
            'timestamp' => date('c'),
            'query' => [
                'reference' => $reference,
            ],
            'result' => $result,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function status(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $electionId = (int) ($_GET['election_id'] ?? 1);
        $blockchain = new VoteBlockchain();
        $status = $blockchain->getChainStatus($electionId);

        http_response_code(200);
        echo json_encode([
            'api_version' => '1.0',
            'timestamp' => date('c'),
            'blockchain' => $status,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function block(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $electionId = (int) ($_GET['election_id'] ?? 1);
        $hash = isset($_GET['hash']) ? trim($_GET['hash']) : null;
        $index = isset($_GET['index']) ? (int) $_GET['index'] : null;

        if ($hash === null && $index === null) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing parameter: specify either ?hash=BLOCK_HASH or ?index=BLOCK_INDEX',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $blockchain = new VoteBlockchain();
        $block = $blockchain->getBlock($electionId, $hash, $index);

        if (! $block) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'error' => 'Block not found on ledger.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'api_version' => '1.0',
            'timestamp' => date('c'),
            'block' => $block,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function receiveBlock(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, X-Node-Token, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        $secret = (string) (voting_config('nodes.secret_token', 'orgchain-node-auth-secret-2026') ?? '');
        $token = $_SERVER['HTTP_X_NODE_TOKEN'] ?? '';
        if ($token === '' && ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
            }
        }

        if ($secret !== '' && ! hash_equals($secret, (string) $token)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Unauthorized node. Invalid or missing node security token.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        if (! is_array($payload) || empty($payload['block']) || ! is_array($payload['block'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid payload format. Expected JSON containing block object.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $block = $payload['block'];
        $electionId = (int) ($block['election_id'] ?? 1);
        $targetNode = (int) ($payload['target_node'] ?? voting_config('nodes.current_node', 1));

        // Recompute SHA-256 block hash to ensure data was not tampered with in transit
        $hashData = [
            'election_id' => $electionId,
            'reference_code' => (string) ($block['reference_code'] ?? ''),
            'voter_commitment' => (string) ($block['voter_commitment'] ?? ''),
            'ballot_root' => (string) ($block['ballot_root'] ?? ''),
            'created_at' => (string) ($block['created_at'] ?? ''),
            'previous_hash' => (string) ($block['previous_hash'] ?? ''),
        ];
        $expectedHash = hash('sha256', json_encode($hashData, JSON_UNESCAPED_SLASHES));

        if ($expectedHash !== ($block['block_hash'] ?? '')) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Cryptographic integrity failure: Block hash does not match payload components.',
                'expected' => $expectedHash,
                'received' => $block['block_hash'] ?? '',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $blockchain = new VoteBlockchain();
        $result = $blockchain->appendBlockToLocalLedger($targetNode, $electionId, $block);

        if (($result['status'] ?? '') !== 'ok') {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => $result['message'] ?? 'Failed to write block to local node ledger.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'status' => 'ok',
            'node' => $targetNode,
            'block_hash' => $block['block_hash'],
            'reference_code' => $block['reference_code'],
            'message' => 'Block successfully verified and appended to node ledger.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function nodeStatus(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $electionId = (int) ($_GET['election_id'] ?? 1);
        $node = (int) ($_GET['node'] ?? voting_config('nodes.current_node', 1));

        $blockchain = new VoteBlockchain();
        $status = $blockchain->getNodeStatus($node, $electionId);

        http_response_code(200);
        echo json_encode([
            'api_version' => '1.0',
            'timestamp' => date('c'),
            'node_info' => $status,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function nodeVerifyBlock(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $electionId = (int) ($_GET['election_id'] ?? 1);
        $node = (int) ($_GET['node'] ?? voting_config('nodes.current_node', 1));
        $reference = trim($_GET['reference'] ?? '');
        $blockHash = trim($_GET['block_hash'] ?? '');

        if ($reference === '' || $blockHash === '') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing required parameters: reference and block_hash are required.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $blockchain = new VoteBlockchain();
        $findResult = $blockchain->findOnLocalNode($node, $electionId, $reference, $blockHash);

        http_response_code(200);
        echo json_encode([
            'api_version' => '1.0',
            'timestamp' => date('c'),
            'result' => $findResult,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

