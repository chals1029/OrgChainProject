<?php

namespace Tests\Feature;

use App\Services\Health\SystemHealthSnapshot;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    private string $healthDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthDirectory = sys_get_temp_dir().'/orgchain-health-test-'.bin2hex(random_bytes(12));
        mkdir($this->healthDirectory, 0700);
        config(['system-health.snapshot_path' => $this->healthDirectory.'/snapshot.json']);
    }

    protected function tearDown(): void
    {
        if (is_file($this->healthDirectory.'/snapshot.json')) {
            unlink($this->healthDirectory.'/snapshot.json');
        }
        rmdir($this->healthDirectory);
        parent::tearDown();
    }

    private function save(string $state = 'operational', int $age = 5): array
    {
        $entry = [
            'status' => $state,
            'checkedAt' => gmdate('Y-m-d\TH:i:s\Z', time() - $age),
            'startedAt' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600),
        ];
        $services = array_fill_keys(SystemHealthSnapshot::SERVICES, $entry);
        file_put_contents($this->healthDirectory.'/snapshot.json', json_encode(['services' => $services], JSON_THROW_ON_ERROR));

        return $entry;
    }

    public function test_public_observations_strip_diagnostics_and_do_not_require_database_sessions(): void
    {
        $entry = $this->save();
        $services = array_fill_keys(SystemHealthSnapshot::SERVICES, $entry + ['diagnostics' => ['password' => 'synthetic-private-value', 'file' => '/private/synthetic-document.pdf']]);
        $services['internal-debug'] = ['secret' => 'synthetic-internal-only'];
        file_put_contents($this->healthDirectory.'/snapshot.json', json_encode(['services' => $services, 'credentials' => 'synthetic-private-credential'], JSON_THROW_ON_ERROR));
        config([
            'session.driver' => 'database',
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => 1,
            'database.connections.mysql.database' => 'not_a_real_database',
        ]);

        $response = $this->getJson('/health/services');

        $response->assertOk()->assertExactJson(['services' => array_fill_keys(SystemHealthSnapshot::SERVICES, $entry)]);
        $this->assertFalse($response->headers->has('Set-Cookie'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_expired_observations_are_unknown_not_green(): void
    {
        $entry = $this->save(age: 901);

        $this->getJson('/health/services/database')->assertStatus(503)->assertExactJson([
            'status' => 'unknown', 'checkedAt' => $entry['checkedAt'], 'startedAt' => $entry['startedAt'],
        ]);
    }

    public function test_missing_and_corrupt_snapshots_do_not_claim_availability(): void
    {
        $this->getJson('/health/services/blockchain')->assertStatus(503)->assertExactJson([
            'status' => 'unknown', 'checkedAt' => null, 'startedAt' => null,
        ]);
        file_put_contents($this->healthDirectory.'/snapshot.json', '{corrupt synthetic data');
        $this->getJson('/health/services/blockchain')->assertStatus(503)->assertJsonPath('status', 'unknown');
    }

    public function test_future_or_impossible_dates_cannot_become_healthy(): void
    {
        $this->save(age: -600);
        $this->getJson('/health/services/private-storage')->assertStatus(503)->assertJsonPath('checkedAt', null);
        $entry = ['status' => 'operational', 'checkedAt' => '2026-02-30T12:00:00Z', 'startedAt' => '2026-01-01T00:00:00Z'];
        file_put_contents($this->healthDirectory.'/snapshot.json', json_encode(['services' => ['private-storage' => $entry]], JSON_THROW_ON_ERROR));
        $this->getJson('/health/services/private-storage')->assertStatus(503)->assertJsonPath('status', 'unknown');
    }

    public function test_degraded_and_unavailable_states_keep_their_distinct_signals(): void
    {
        $this->save('degraded');
        $this->getJson('/health/services/document-processing')->assertOk()->assertJsonPath('status', 'degraded');
        $this->save('unavailable');
        $this->getJson('/health/services/document-processing')->assertStatus(503)->assertJsonPath('status', 'unavailable');
    }

    public function test_non_allowlisted_service_names_are_not_exposed(): void
    {
        $this->save();
        $this->getJson('/health/services/internal-debug')->assertNotFound();
        $this->getJson('/health/services/mail-password')->assertNotFound();
    }
}
