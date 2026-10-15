<?php

namespace Tests;

use App\Models\OfficeUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof OfficeUser && ($guard ?? $this->app['auth']->getDefaultDriver()) === 'office') {
            // A later withSession() can deliberately replace this with a stale credential version.
            $this->withSession(['office_auth_state' => [
                'id' => (int) $user->id,
                'version' => (int) $user->auth_version,
            ]]);
        }

        return $this;
    }
}
