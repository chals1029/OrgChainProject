<?php

namespace Tests\Unit;

use App\Services\BesuChainService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BesuChainServiceTest extends TestCase
{
    public function test_besu_anchor_builds_and_submits_a_signed_transaction(): void
    {
        if (! extension_loaded('gmp')) {
            $this->markTestSkipped('PHP GMP is not enabled in this test runtime.');
        }

        config([
            'besu.enabled' => true,
            'besu.rpc_url' => 'http://besu.test',
            'besu.contract_address' => '0x1111111111111111111111111111111111111111',
            'besu.signer_private_key' => str_pad(dechex(0x101), 64, '0', STR_PAD_LEFT),
            'besu.signer_address' => '0x25a71a07cecf1753ee65b00e0a3aaef7e0f51c0f',
            'besu.chain_id' => 20260920,
            'besu.gas_price_wei' => '1',
            'besu.receipt_timeout_seconds' => 5,
        ]);

        Http::fakeSequence()
            ->push(['result' => '0x0'])
            ->push(['result' => '0xaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])
            ->push(['result' => [
                'status' => '0x1',
                'blockNumber' => '0x2a',
                'blockHash' => '0xbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            ]]);

        $result = app(BesuChainService::class)->anchor(
            'vote',
            str_repeat('ab', 32),
            str_repeat('cd', 32),
            ['index' => 1, 'sealed_at' => '2026-09-20T00:00:00+00:00'],
        );

        $this->assertSame('besu', $result['chain_driver']);
        $this->assertSame(42, $result['chain_block_number']);
        $this->assertSame(4, $result['nodes_confirmed']);
        $this->assertSame('0xaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $result['chain_tx_hash']);
        $this->assertCount(3, Http::recorded());
    }
}

