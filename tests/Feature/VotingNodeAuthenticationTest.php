<?php

namespace Tests\Feature;

use App\VotingSystem\Controllers\ApiController;
use App\VotingSystem\Core\VoteBlockchain;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VotingNodeAuthenticationTest extends TestCase
{
    private string $originalStoragePath;

    private string $temporaryStoragePath;

    private array $block;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalStoragePath = $this->app->storagePath();
        $this->temporaryStoragePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'orgchain-node-auth-'.bin2hex(random_bytes(12));
        $this->app->useStoragePath($this->temporaryStoragePath);
        config(['besu.enabled' => false, 'voting.nodes.current_node' => 1]);

        $payload = [
            'election_id' => 1,
            'reference_code' => 'SYNTHETIC-NODE-REFERENCE',
            'voter_commitment' => hash('sha256', 'synthetic-commitment'),
            'ballot_root' => hash('sha256', 'synthetic-ballot'),
            'created_at' => '2026-01-01 00:00:00',
            'previous_hash' => VoteBlockchain::GENESIS_HASH,
        ];
        $this->block = $payload + [
            'index' => 1,
            'block_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $result = (new VoteBlockchain)->appendBlockToLocalLedger(1, 1, $this->block);
        $this->assertSame('ok', $result['status']);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStoragePath);
        File::deleteDirectory($this->temporaryStoragePath);
        parent::tearDown();
    }

    #[DataProvider('rejectedAuthenticationCases')]
    public function test_private_node_apis_reject_unauthorized_requests_without_ledger_or_secret_disclosure(
        string $method,
        string $secret,
        array $headers,
    ): void {
        config(['voting.nodes.secret_token' => $secret]);
        $path = storage_path('app/voting/chain/node-1/election-1.jsonl');
        $before = file_get_contents($path);

        [$status, $body, $raw] = $this->invokeApi($method, $headers, $this->nodeQuery() + ['token' => $secret]);

        $this->assertSame(403, $status);
        $this->assertFalse($body['ok']);
        $this->assertIsString($body['error']);
        $this->assertSame(['ok', 'error'], array_keys($body));
        if ($secret !== '') {
            $this->assertStringNotContainsString($secret, $raw);
        }
        foreach (['reference_code', 'block_hash', 'voter_commitment', 'ballot_root'] as $field) {
            $this->assertStringNotContainsString($this->block[$field], $raw);
        }
        $this->assertSame($before, file_get_contents($path));
    }

    public static function rejectedAuthenticationCases(): array
    {
        $cases = [
            'empty secret and missing token' => ['', []],
            'empty secret and supplied token' => ['', ['HTTP_X_NODE_TOKEN' => 'fixture-node-token']],
            'missing token' => ['fixture-node-token', []],
            'empty token' => ['fixture-node-token', ['HTTP_X_NODE_TOKEN' => '']],
            'wrong token' => ['fixture-node-token', ['HTTP_X_NODE_TOKEN' => 'fixture-wrong-token']],
            'wrong bearer token' => ['fixture-node-token', ['HTTP_AUTHORIZATION' => 'Bearer fixture-wrong-token']],
            'empty bearer token' => ['fixture-node-token', ['HTTP_AUTHORIZATION' => 'Bearer ']],
            'wrong header cannot be overridden by bearer' => ['fixture-node-token', [
                'HTTP_X_NODE_TOKEN' => 'fixture-wrong-token',
                'HTTP_AUTHORIZATION' => 'Bearer fixture-node-token',
            ]],
        ];
        $result = [];
        foreach (['receiveBlock', 'nodeStatus', 'nodeVerifyBlock'] as $method) {
            foreach ($cases as $name => [$secret, $headers]) {
                $result[$method.' '.$name] = [$method, $secret, $headers];
            }
        }

        return $result;
    }

    #[DataProvider('acceptedAuthenticationCases')]
    public function test_matching_node_token_allows_private_node_processing(string $method, array $headers): void
    {
        config(['voting.nodes.secret_token' => 'fixture-node-token']);
        [$status, $body, $raw] = $this->invokeApi($method, $headers, $this->nodeQuery());

        $this->assertStringNotContainsString('fixture-node-token', $raw);
        if ($method === 'receiveBlock') {
            // An authenticated request with no body reaches payload validation, not the ledger writer.
            $this->assertSame(400, $status);
            $this->assertFalse($body['ok']);
        } elseif ($method === 'nodeStatus') {
            $this->assertSame(200, $status);
            $this->assertSame(1, $body['node_info']['total_blocks']);
            $this->assertSame($this->block['block_hash'], $body['node_info']['latest_block_hash']);
        } else {
            $this->assertSame(200, $status);
            $this->assertSame('ok', $body['result']['status']);
            $this->assertSame(1, $body['result']['node']);
        }
    }

    public static function acceptedAuthenticationCases(): array
    {
        $result = [];
        foreach (['receiveBlock', 'nodeStatus', 'nodeVerifyBlock'] as $method) {
            $result[$method.' header'] = [$method, ['HTTP_X_NODE_TOKEN' => 'fixture-node-token']];
            $result[$method.' bearer'] = [$method, ['HTTP_AUTHORIZATION' => 'Bearer fixture-node-token']];
            $result[$method.' empty header with bearer'] = [$method, [
                'HTTP_X_NODE_TOKEN' => '',
                'HTTP_AUTHORIZATION' => 'Bearer fixture-node-token',
            ]];
        }

        return $result;
    }

    public function test_private_verification_authentication_precedes_parameter_validation(): void
    {
        config(['voting.nodes.secret_token' => 'fixture-node-token']);
        [$status] = $this->invokeApi('nodeVerifyBlock');
        $this->assertSame(403, $status);
        [$status, $body] = $this->invokeApi('nodeVerifyBlock', ['HTTP_X_NODE_TOKEN' => 'fixture-node-token']);
        $this->assertSame(400, $status);
        $this->assertFalse($body['ok']);
    }

    public function test_public_blockchain_apis_do_not_require_a_node_token(): void
    {
        config(['voting.nodes.secret_token' => 'fixture-node-token']);
        [$status, $body] = $this->invokeApi('status', [], ['election_id' => 1]);
        $this->assertSame(200, $status);
        $this->assertSame(1, $body['blockchain']['total_sealed_blocks']);

        [$status, $body] = $this->invokeApi('block', [], ['election_id' => 1, 'index' => 1]);
        $this->assertSame(200, $status);
        $this->assertSame($this->block, $body['block']);

        [$status, $body] = $this->invokeApi('verify');
        $this->assertSame(400, $status);
        $this->assertFalse($body['ok']);
    }

    private function nodeQuery(): array
    {
        return [
            'node' => 1,
            'election_id' => 1,
            'reference' => $this->block['reference_code'],
            'block_hash' => $this->block['block_hash'],
        ];
    }

    private function invokeApi(string $method, array $headers = [], array $query = []): array
    {
        $originalServer = $_SERVER;
        $originalQuery = $_GET;
        $_SERVER['REQUEST_METHOD'] = $method === 'receiveBlock' ? 'POST' : 'GET';
        unset($_SERVER['HTTP_X_NODE_TOKEN'], $_SERVER['HTTP_AUTHORIZATION']);
        $_SERVER = array_replace($_SERVER, $headers);
        $_GET = $query;
        http_response_code(200);

        ob_start();
        try {
            (new ApiController)->{$method}();
            $raw = ob_get_contents();

            return [http_response_code(), json_decode($raw, true, flags: JSON_THROW_ON_ERROR), $raw];
        } finally {
            ob_end_clean();
            $_SERVER = $originalServer;
            $_GET = $originalQuery;
            http_response_code(200);
        }
    }
}
