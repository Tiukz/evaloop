<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Presentation only: retain fixture markers in storage and editable field values.
        Blade::directive('display', fn ($expression) => "<?php echo e(preg_replace('/\\[(?:SIMULASI|TECFEST)\\]\\s*/iu', '', ($expression) ?? '')); ?>");

        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $email = is_string($email) ? strtolower(trim($email)) : 'invalid';

            return [
                Limit::perMinute(5)->by('login:'.hash('sha256', $email.'|'.$request->ip())),
                Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            ];
        });
    }
}
