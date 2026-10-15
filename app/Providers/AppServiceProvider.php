<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\RateLimiter;
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
        TrustProxies::at(array_filter(array_map('trim', explode(',', (string) config('orgchain.trusted_proxies')))));

        RateLimiter::for('student-code', function (Request $request) {
            $account = hash('sha256', trim((string) $request->input('sr_code')));

            return [
                Limit::perMinute(3)->by('student-code:minute:'.$account),
                Limit::perHour(10)->by('student-code:hour:'.$account),
                Limit::perMinute(20)->by('student-code:ip:'.$request->ip()),
            ];
        });
        RateLimiter::for('student-verify', fn (Request $request) => Limit::perMinute(10)
            ->by('student-verify:'.$request->ip().':'.hash('sha256', trim((string) $request->input('sr_code')))));
        foreach (['office-login', 'system-admin-login'] as $limiter) {
            RateLimiter::for($limiter, function (Request $request) use ($limiter) {
                if (! $request->isMethod('post')) {
                    return Limit::none();
                }

                return [
                    Limit::perMinute(5)->by($limiter.':account:'.hash('sha256', strtolower(trim((string) $request->input('email'))))),
                    Limit::perMinute(30)->by($limiter.':ip:'.$request->ip()),
                ];
            });
        }

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
