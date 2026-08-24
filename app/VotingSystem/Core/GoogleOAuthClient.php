<?php

namespace App\VotingSystem\Core;

use RuntimeException;

class GoogleOAuthClient
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(
        private readonly ?string $redirectUriOverride = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function authorizationUrl(string $state, string $nonce): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account',
            'hd' => $this->config('google.allowed_domain', 'g.batstate-u.edu.ph'),
        ]);
    }

    public function userFromCode(string $code, string $expectedNonce): array
    {
        $tokens = $this->requestTokens($code);
        $idToken = (string) ($tokens['id_token'] ?? '');

        if ($idToken === '') {
            throw new RuntimeException('Google did not return an identity token.');
        }

        $claims = $this->decodeJwtPayload($idToken);
        $this->validateClaims($claims, $expectedNonce);

        return [
            'email' => strtolower(trim((string) ($claims['email'] ?? ''))),
            'email_verified' => filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => trim((string) ($claims['name'] ?? '')),
            'picture' => trim((string) ($claims['picture'] ?? '')),
        ];
    }

    public function redirectUri(): string
    {
        $override = trim((string) ($this->redirectUriOverride ?? ''));

        if ($override !== '') {
            return $override;
        }

        $configured = trim((string) $this->config('google.redirect_uri', ''));

        if ($configured !== '') {
            return $configured;
        }

        $url = voting_url('/auth/google/callback');

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        $scheme = request_is_secure() ? 'https' : 'http';
        $host = trusted_request_host();

        return $scheme . '://' . $host . $url;
    }

    private function requestTokens(string $code): array
    {
        $payload = [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ];

        return $this->postJson(self::TOKEN_URL, $payload);
    }

    private function postJson(string $url, array $payload): array
    {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_TIMEOUT => 15,
            ]);

            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new RuntimeException('Google OAuth request failed: ' . $error);
            }

            return $this->decodeHttpResponse((string) $response, $status);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException('Google OAuth request failed.');
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $match)) {
                $status = (int) $match[1];
                break;
            }
        }

        return $this->decodeHttpResponse($response, $status);
    }

    private function decodeHttpResponse(string $response, int $status): array
    {
        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Google returned an invalid response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google OAuth request failed.');
            throw new RuntimeException($message);
        }

        return $data;
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            throw new RuntimeException('Google returned an invalid identity token.');
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $json = base64_decode($payload, true);

        if ($json === false) {
            throw new RuntimeException('Google returned an unreadable identity token.');
        }

        $claims = json_decode($json, true);

        if (!is_array($claims)) {
            throw new RuntimeException('Google returned an invalid identity token payload.');
        }

        return $claims;
    }

    private function validateClaims(array $claims, string $expectedNonce): void
    {
        $issuer = (string) ($claims['iss'] ?? '');
        if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new RuntimeException('Google identity token issuer is invalid.');
        }

        if (!hash_equals($this->clientId(), (string) ($claims['aud'] ?? ''))) {
            throw new RuntimeException('Google identity token audience is invalid.');
        }

        if ((int) ($claims['exp'] ?? 0) < time()) {
            throw new RuntimeException('Google identity token has expired.');
        }

        if ($expectedNonce !== '' && !hash_equals($expectedNonce, (string) ($claims['nonce'] ?? ''))) {
            throw new RuntimeException('Google identity token nonce is invalid.');
        }

        if (trim((string) ($claims['email'] ?? '')) === '') {
            throw new RuntimeException('Google did not provide an email address.');
        }
    }

    private function clientId(): string
    {
        return trim((string) $this->config('google.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) $this->config('google.client_secret', ''));
    }

    /**
     * Read a voting config value, working both inside the voting system
     * runtime (where the voting_config() helper exists) and inside a normal
     * Laravel request (where we fall back to Laravel's config()).
     */
    private function config(string $key, mixed $default = null): mixed
    {
        if (function_exists('App\VotingSystem\Core\voting_config')
            || function_exists('voting_config')
        ) {
            return voting_config($key, $default);
        }

        return config('voting.' . $key, $default);
    }
}
