<?php

namespace Tests\Unit;

use App\Services\BesuTransactionSigner;
use Tests\TestCase;

class BesuTransactionSignerTest extends TestCase
{
    public function test_signed_transaction_matches_the_canonical_eip155_replay_protection_vector(): void
    {
        // Public example key and transaction: https://eips.ethereum.org/EIPS/eip-155
        config(['besu.signer_private_key' => str_repeat('46', 32)]);
        $signed = app(BesuTransactionSigner::class)->signLegacy([
            'nonce' => '0x9', 'gasPrice' => '0x4a817c800', 'gas' => '0x5208',
            'to' => '0x3535353535353535353535353535353535353535',
            'value' => '0xde0b6b3a7640000', 'data' => '0x', 'chainId' => 1,
        ]);
        $this->assertSame('0xf86c098504a817c800825208943535353535353535353535353535353535353535880de0b6b3a76400008025a028ef61340bd939bc2195fe537567866003e1a15d3c71ff63e1590620aa636276a067cbe9d8997f761aecb703304b3800ccf555c9f3dc64214b297fb1966a3b6d83', $signed['raw_transaction']);
    }

    public function test_address_derivation_retains_the_existing_besu_fixture_identity(): void
    {
        $key = str_pad(dechex(0x101), 64, '0', STR_PAD_LEFT);
        $this->assertSame('0x25a71a07cecf1753ee65b00e0a3aaef7e0f51c0f', app(BesuTransactionSigner::class)->addressFromPrivateKey($key));
    }
}
