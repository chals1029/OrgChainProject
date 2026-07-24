<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class VotingSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('voting.php'), 'voting');
    }

    public function boot(): void
    {
        // Helpers are loaded on first voting request by the Kernel.
        // Ensure storage dirs exist.
        $dirs = [
            storage_path('app/voting'),
            storage_path('app/voting/uploads'),
            storage_path('app/voting/uploads/candidates'),
            storage_path('app/voting/sessions'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }
}
