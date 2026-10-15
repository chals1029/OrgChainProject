<?php

namespace App\Services\Health;

use App\Services\BesuChainService;
use App\VotingSystem\Core\GoogleOAuthClient;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\Http;
use PDO;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;

/**
 * Private, read-only readiness measurements. Nothing except a coarse enum leaves
 * this class; provider payloads, connection details and exceptions stay private.
 */
class CoreReadinessProbe
{
    public function check(string $service): string
    {
        try {
            return match ($service) {
                'database' => $this->database(),
                'blockchain' => $this->blockchain(),
                'institutional-sign-in' => $this->institutionalSignIn(),
                'mail-provider' => $this->mailProvider(),
                default => 'unknown',
            };
        } catch (Throwable) {
            return 'unavailable';
        }
    }

    private function timeout(): int
    {
        return max(1, min(15, (int) config('system-health.timeout_seconds', 4)));
    }

    private function database(): string
    {
        // Voting normally uses Laravel's default connection. Do not invoke its
        // Database adapter: opening it can run application schema migrations.
        $healthy = 0;
        foreach ([(string) config('database.default', ''), 'orgchain'] as $name) {
            $connection = null;
            try {
                $configured = config('database.connections.'.$name);
                if ($name === '' || ! is_array($configured)) {
                    continue;
                }

                $settings = (new ConfigurationUrlParser)->parseConfiguration($configured);
                $driver = $settings['driver'] ?? '';
                $settings['options'] = (array) ($settings['options'] ?? []);
                $settings['options'][PDO::ATTR_PERSISTENT] = false;

                if (in_array($driver, ['mysql', 'mariadb', 'sqlite', 'pgsql'], true)) {
                    $settings['options'][PDO::ATTR_TIMEOUT] = $this->timeout();
                }
                if (in_array($driver, ['mysql', 'mariadb'], true)
                    && defined('PDO::MYSQL_ATTR_READ_TIMEOUT')) {
                    $settings['options'][constant('PDO::MYSQL_ATTR_READ_TIMEOUT')] = $this->timeout();
                }
                if ($driver === 'pgsql') {
                    $settings['server_options']['statement_timeout'] = $this->timeout() * 1000;
                }
                if ($driver === 'sqlite') {
                    // Opening a probe must not change persistent journal settings.
                    unset($settings['pragmas'], $settings['journal_mode'], $settings['synchronous']);
                    $settings['busy_timeout'] = $this->timeout() * 1000;
                    if (defined('PDO::SQLITE_ATTR_OPEN_FLAGS') && defined('PDO::SQLITE_OPEN_READONLY')) {
                        $settings['options'][constant('PDO::SQLITE_ATTR_OPEN_FLAGS')] = constant('PDO::SQLITE_OPEN_READONLY');
                    }
                }

                // This isolated connection never changes the shared DB manager,
                // attaches query listeners, or reads any application table.
                $connection = app(ConnectionFactory::class)->make($settings, $name);
                $statement = $connection->getPdo()->query('SELECT 1');
                if ($statement !== false && (string) $statement->fetchColumn() === '1') {
                    $healthy++;
                }
                if ($statement !== false) {
                    $statement->closeCursor();
                }
            } catch (Throwable) {
                // Only the count of successful measurements is retained.
            } finally {
                $connection?->disconnect();
            }
        }

        return match ($healthy) {
            2 => 'operational',
            1 => 'degraded',
            default => 'unavailable',
        };
    }

    private function blockchain(): string
    {
        if (! (bool) config('besu.enabled', false)) {
            return 'unknown';
        }
        $urls = config('system-health.rpc_urls', []);
        $chainId = (int) config('besu.chain_id', 0);
        if (! is_array($urls) || $urls === [] || $chainId < 1) {
            return 'unknown';
        }
        foreach ($urls as $url) {
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)
                || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                return 'unknown';
            }
        }
        $urls = array_values(array_unique($urls));
        if (count($urls) !== max(1, (int) config('besu.validator_count', 4))) {
            return 'unknown';
        }

        try {
            // Resolving this read-only accessor does not read the signer key.
            $contract = app(BesuChainService::class)->contractAddress();
        } catch (Throwable) {
            return 'unknown';
        }

        $batch = [
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'eth_chainId', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'eth_getBlockByNumber', 'params' => ['latest', false]],
            ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'eth_getCode', 'params' => [$contract, 'latest']],
        ];
        $responses = Http::pool(function (Pool $pool) use ($urls, $batch): void {
            foreach ($urls as $index => $url) {
                $pool->as((string) $index)
                    ->timeout($this->timeout())
                    ->connectTimeout($this->timeout())
                    ->withOptions(['allow_redirects' => false, 'verify' => true])
                    ->acceptJson()
                    ->post($url, $batch);
            }
        });

        $healthy = 0;
        $now = time();
        foreach ($responses as $response) {
            if ($this->healthyNode($response, $chainId, $now)) {
                $healthy++;
            }
        }
        $total = count($urls);

        if ($healthy === $total) {
            return 'operational';
        }

        return $healthy >= (int) ceil(2 * $total / 3) ? 'degraded' : 'unavailable';
    }

    private function healthyNode(mixed $response, int $chainId, int $now): bool
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return false;
        }
        $body = $response->json();
        if (! is_array($body) || ! array_is_list($body) || count($body) !== 3) {
            return false;
        }
        $results = [];
        foreach ($body as $entry) {
            if (! is_array($entry) || ($entry['jsonrpc'] ?? null) !== '2.0'
                || ! in_array($entry['id'] ?? null, [1, 2, 3], true)
                || array_key_exists('error', $entry) || ! array_key_exists('result', $entry)
                || array_key_exists($entry['id'], $results)) {
                return false;
            }
            $results[$entry['id']] = $entry['result'];
        }

        $block = $results[2];
        $height = is_array($block) ? $this->quantity($block['number'] ?? null) : null;
        $timestamp = is_array($block) ? $this->quantity($block['timestamp'] ?? null) : null;
        $maxAge = max(1, (int) config('system-health.block_max_age_seconds', 30));

        return $this->quantity($results[1]) === $chainId
            && $height !== null
            && $timestamp !== null && $timestamp > 0
            && $timestamp <= $now && $now - $timestamp <= $maxAge
            && is_string($results[3])
            && preg_match('/^0x(?:[0-9a-f]{2})+$/iD', $results[3]) === 1;
    }

    private function quantity(mixed $value): ?int
    {
        if (! is_string($value) || preg_match('/^0x(?:0|[1-9a-f][0-9a-f]*)$/iD', $value) !== 1
            || strlen($value) > 18) {
            return null;
        }
        $number = hexdec(substr($value, 2));

        return is_int($number) ? $number : null;
    }

    private function institutionalSignIn(): string
    {
        $oauth = new GoogleOAuthClient;
        $domain = trim((string) config('voting.google.allowed_domain', ''));
        $clientId = trim((string) config('voting.google.client_id', ''));
        $callback = trim((string) config('voting.google.redirect_uri', ''));
        if ($callback === '' && ! $this->publicHttpsUrl((string) config('voting.app_url', ''))) {
            return 'unknown';
        }
        if (! $oauth->isConfigured()
            || preg_match('/^[a-z0-9_-]+\.apps\.googleusercontent\.com$/iD', $clientId) !== 1
            || ! str_contains($domain, '.') || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || ($callback !== '' && ! $this->publicHttpsUrl($callback))
            || ! $this->publicHttpsUrl((string) config('app.url', ''))) {
            return 'unknown';
        }
        $studentOAuth = new GoogleOAuthClient(route('student.auth.google.callback', absolute: true));
        if (! $this->publicHttpsUrl($oauth->redirectUri())
            || ! $this->publicHttpsUrl($studentOAuth->redirectUri())) {
            return 'unknown';
        }

        // This fixed public metadata request sends no OAuth credentials and is
        // not a token exchange, callback request, or completed student login.
        $response = Http::timeout($this->timeout())
            ->connectTimeout($this->timeout())
            ->withOptions(['allow_redirects' => false, 'verify' => true])
            ->acceptJson()
            ->get('https://accounts.google.com/.well-known/openid-configuration');
        $metadata = $response->json();
        if (! $response->successful() || ! is_array($metadata)
            || ($metadata['issuer'] ?? null) !== 'https://accounts.google.com'
            || ($metadata['authorization_endpoint'] ?? null) !== 'https://accounts.google.com/o/oauth2/v2/auth'
            || ($metadata['token_endpoint'] ?? null) !== 'https://oauth2.googleapis.com/token'
            || ($metadata['jwks_uri'] ?? null) !== 'https://www.googleapis.com/oauth2/v3/certs'
            || ! is_array($metadata['response_types_supported'] ?? null)
            || ! in_array('code', $metadata['response_types_supported'], true)) {
            return 'unavailable';
        }

        return 'operational';
    }

    private function publicHttpsUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment'])) {
            return false;
        }
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_contains($host, '.')
            && (bool) filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            && preg_match('/\.(?:localhost|local|internal|test|invalid|example)$/iD', $host) !== 1;
    }

    private function mailProvider(): string
    {
        $name = (string) config('mail.default', '');
        $laravel = config('mail.mailers.'.$name);
        if (! is_array($laravel)) {
            return 'unknown';
        }
        if (! empty($laravel['url'])) {
            $laravel = (new ConfigurationUrlParser)->parseConfiguration($laravel);
            $laravel['transport'] = $laravel['driver'] ?? $laravel['transport'] ?? '';
        }
        $voting = config('voting.mail', []);
        if (! is_array($voting) || ($voting['mailer'] ?? '') !== 'smtp'
            || ! in_array(strtolower((string) ($voting['encryption'] ?? '')), ['ssl', 'tls'], true)) {
            return 'unknown';
        }
        $voting['transport'] = 'smtp';
        $voting['scheme'] = strtolower((string) $voting['encryption']) === 'ssl' ? 'smtps' : 'smtp';
        $voting['implicit_tls'] = $voting['scheme'] === 'smtps';

        // These are separate active mail pathways. A healthy default Laravel
        // transport must not conceal an unconfigured or broken voting transport.
        $statuses = [$this->smtp($laravel), $this->smtp($voting)];
        if (in_array('unavailable', $statuses, true)) {
            return 'unavailable';
        }

        return in_array('unknown', $statuses, true) ? 'unknown' : 'operational';
    }

    private function smtp(array $settings): string
    {
        $host = trim((string) ($settings['host'] ?? ''));
        $port = (int) ($settings['port'] ?? 0);
        $scheme = $settings['scheme'] ?? ($port === 465 ? 'smtps' : 'smtp');
        $implicitTls = $settings['implicit_tls'] ?? ($scheme === 'smtps' || $port === 465);
        $username = (string) ($settings['username'] ?? '');
        $password = (string) ($settings['password'] ?? '');
        if (($settings['transport'] ?? '') !== 'smtp' || $host === '' || $port < 1 || $port > 65535
            || ! in_array($scheme, ['smtp', 'smtps'], true)
            || (($username === '') !== ($password === ''))
            || ($scheme === 'smtp' && isset($settings['auto_tls'])
                && ! filter_var($settings['auto_tls'], FILTER_VALIDATE_BOOLEAN))) {
            return 'unknown';
        }

        // A fresh Symfony transport avoids changing or stopping the application's
        // cached mailer. Its logger is deliberately disconnected from Laravel.
        $transport = new class($host, $port, $implicitTls, null, new NullLogger) extends EsmtpTransport
        {
            private bool $startTlsAccepted = false;

            public function executeCommand(string $command, array $codes): string
            {
                $response = parent::executeCommand($command, $codes);
                if ($command === "STARTTLS\r\n") {
                    $this->startTlsAccepted = true;
                }

                return $response;
            }

            public function encryptedSession(): bool
            {
                // start() must also succeed: accepting STARTTLS alone is not a
                // completed TLS handshake. This rejects Symfony's HELO fallback.
                return $this->getStream()->isTLS() || $this->startTlsAccepted;
            }
        };
        $transport->setRequireTls(true);
        $transport->setAutoTls(true);
        $transport->setUsername($username);
        $transport->setPassword($password);
        if (! empty($settings['local_domain'])) {
            $transport->setLocalDomain((string) $settings['local_domain']);
        }
        $stream = $transport->getStream();
        $stream->setTimeout($this->timeout());
        if (! empty($settings['source_ip'])) {
            $stream->setSourceIp((string) $settings['source_ip']);
        }
        $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        $ssl = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => trim($host, '[]'),
            'crypto_method' => $crypto,
        ];
        if (! empty($settings['peer_fingerprint'])) {
            $ssl['peer_fingerprint'] = $settings['peer_fingerprint'];
        }
        $stream->setStreamOptions(['ssl' => $ssl]);

        try {
            // Greeting, EHLO, mandatory verified TLS, and configured AUTH when
            // advertised. Never create a message, envelope, or recipient.
            $transport->start();

            return $transport->encryptedSession() ? 'operational' : 'unavailable';
        } catch (Throwable) {
            return 'unavailable';
        } finally {
            try {
                $transport->stop();
            } catch (Throwable) {
                // Teardown errors must not reveal SMTP conversations.
            } finally {
                $stream->terminate();
            }
        }
    }
}
