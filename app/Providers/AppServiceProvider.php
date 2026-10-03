<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = (string) config('app.url');

        // Keep asset/login redirects on the public tunnel host (ngrok / Cloudflare).
        if ($appUrl !== '' && str_starts_with($appUrl, 'http')) {
            URL::forceRootUrl($appUrl);
        }

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        // When accessed via public domain (e.g. orgchain.tech) or any non-localhost host,
        // bypass the local Vite dev server hot file so browsers load compiled production assets.
        if (! $this->app->runningInConsole()) {
            $host = request()->getHost();
            if (! in_array($host, ['127.0.0.1', 'localhost', '::1', 'orgchain.test'], true)) {
                Vite::useHotFile(storage_path('framework/nonexistent-hot'));
            }
        }
    }
}
