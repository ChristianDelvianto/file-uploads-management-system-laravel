<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', function (Request $request) {
            $prefix = $request->user()?->id ?: $request->ip();

            $key = md5($prefix . ':' . $request->url());

            return Limit::perHour(1000)->by($key);
        });

        RateLimiter::for('upload_chunks', function (Request $request) {
            $key = md5($request->user()->id . ':' . $request->route('upload')->uuid);

            return Limit::perHour(9999)->by($key);
        });
    }
}
