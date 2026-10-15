<?php

namespace Tests\Unit;

use App\Services\Health\CoreReadinessProbe;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoreReadinessProbeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'besu.enabled' => true,
            'besu.chain_id' => 1234,
            'besu.validator_count' => 4,
            'besu.contract_address' => '0x1111111111111111111111111111111111111111',
            'system-health.rpc_urls' => array_map(fn (int $node) => 'http://readiness-node-'.$node.'.test', range(1, 4)),
        ]);
        Http::preventStrayRequests();
    }

    private function blockResponses(int $timestamp, string $code = '0x6000', string $chain = '0x4d2'): array
    {
        return [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => $chain],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['number' => '0x7b', 'timestamp' => '0x'.dechex($timestamp)]],
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => $code],
        ];
    }

    public static function quorums(): array
    {
        return ['all validators' => [4, 'operational'], 'quorum retained' => [3, 'degraded'], 'quorum lost' => [2, 'unavailable']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('quorums')]
    public function test_blockchain_readiness_distinguishes_all_nodes_quorum_and_quorum_loss(int $healthy, string $expected): void
    {
        Http::fake(function ($request) use ($healthy) {
            preg_match('/readiness-node-(\d)/', $request->url(), $match);

            return (int) $match[1] <= $healthy
                ? Http::response($this->blockResponses(time()), 200)
                : Http::response([], 503);
        });
        $this->assertSame($expected, app(CoreReadinessProbe::class)->check('blockchain'));
    }

    public function test_responding_nodes_with_stale_blocks_are_not_healthy(): void
    {
        Http::fake(fn () => Http::response($this->blockResponses(time() - 300), 200));

        $this->assertSame('unavailable', app(CoreReadinessProbe::class)->check('blockchain'));
    }

    public function test_a_wrong_network_cannot_pass_readiness(): void
    {
        Http::fake(fn () => Http::response($this->blockResponses(time(), chain: '0x1'), 200));
        $this->assertSame('unavailable', app(CoreReadinessProbe::class)->check('blockchain'));
    }

    public function test_an_absent_contract_cannot_pass_readiness(): void
    {
        Http::fake(fn () => Http::response($this->blockResponses(time(), code: '0x'), 200));
        $this->assertSame('unavailable', app(CoreReadinessProbe::class)->check('blockchain'));
    }

    public function test_an_inactive_besu_driver_is_not_reported_as_integrated(): void
    {
        config(['besu.enabled' => false]);

        $this->assertSame('unknown', app(CoreReadinessProbe::class)->check('blockchain'));
    }
}
