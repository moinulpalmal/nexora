<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureDevCommands();
    }

    /**
     * Processes `php artisan dev` runs alongside the framework defaults
     * (server, queue, logs, vite).
     */
    protected function configureDevCommands(): void
    {
        DevCommands::artisan('schedule:work', 'schedule');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults($this->passwordPolicy(...));
    }

    /**
     * The application's password policy, assembled from `config/auth.php`.
     *
     * There is no environment branch here, and that is the point. The starter
     * kit read `app()->isProduction() ? Password::min(12)->… : null`, so
     * `Password::default()` fell through to Laravel's bare `min(8)` everywhere
     * else and the test suite exercised a policy the application never enforces.
     *
     * @see config('auth.password_policy')
     */
    protected function passwordPolicy(): Password
    {
        /** @var array{min_length: int, mixed_case: bool, letters: bool, numbers: bool, symbols: bool, uncompromised: bool} $policy */
        $policy = config('auth.password_policy');

        $rule = Password::min($policy['min_length']);

        if ($policy['mixed_case']) {
            $rule->mixedCase();
        }

        if ($policy['letters']) {
            $rule->letters();
        }

        if ($policy['numbers']) {
            $rule->numbers();
        }

        if ($policy['symbols']) {
            $rule->symbols();
        }

        if ($policy['uncompromised']) {
            $rule->uncompromised();
        }

        return $rule;
    }
}
