<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$account = UserAccount::query()->where('sr_code', '21-00001')->first();
if (! $account) {
    fwrite(STDERR, "no user_account 21-00001\n");
    exit(1);
}

Auth::guard('student')->login($account);

$request = Request::create('/portal', 'GET');
$response = $kernel->handle($request);

$cookies = [];
foreach ($response->headers->getCookies() as $c) {
    $cookies[] = [
        'name' => $c->getName(),
        'value' => $c->getValue(),
        'domain' => '127.0.0.1',
        'path' => $c->getPath() ?: '/',
        'httpOnly' => $c->isHttpOnly(),
        'secure' => false,
        'sameSite' => 'Lax',
    ];
}

echo json_encode($cookies);
$kernel->terminate($request, $response);
