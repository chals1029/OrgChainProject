<?php

namespace Tests\Feature;

use App\VotingSystem\Controllers\ApiController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VotingNodeAuthenticationTest extends TestCase
{
    #[DataProvider('authenticationCases')]
    public function test_node_receive_requires_a_configured_matching_secret(string $secret, string $token, int $expectedStatus): void
    {
        config(['voting.nodes.secret_token' => $secret]);
        $originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_NODE_TOKEN'] = $token;
        unset($_SERVER['HTTP_AUTHORIZATION']);
        http_response_code(200);

        ob_start();
        try {
            (new ApiController)->receiveBlock();
            $body = json_decode(ob_get_contents(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($expectedStatus, http_response_code());
            $this->assertFalse($body['ok']);
        } finally {
            ob_end_clean();
            $_SERVER = $originalServer;
            http_response_code(200);
        }
    }

    public static function authenticationCases(): array
    {
        return [
            'empty configuration is not anonymous access' => ['', '', 403],
            'a token cannot substitute for server configuration' => ['', 'fixture-node-token', 403],
            'missing token' => ['fixture-node-token', '', 403],
            'wrong token' => ['fixture-node-token', 'fixture-wrong-token', 403],
            'matching token reaches payload validation' => ['fixture-node-token', 'fixture-node-token', 400],
        ];
    }
}
