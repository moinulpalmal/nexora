<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing($this->authenticate(...));

        // Fortify's default confirmation re-runs the guard with
        // `Fortify::username()`, which is the virtual `login` field here, not a
        // column — so the check is done against the signed-in user directly.
        Fortify::confirmPasswordsUsing(
            fn (User $user, ?string $password): bool => Hash::check((string) $password, $user->password),
        );
    }

    /**
     * Resolve the `login` field to a user by email **or** username.
     *
     * Both columns are stored lowercased and Fortify lowercases the input
     * (`lowercase_usernames`), so the comparison is case-insensitive on every
     * driver. Soft-deleted users never match — the default scope excludes them.
     *
     * A deactivated account is refused with its own message, but only once the
     * password has matched: the distinction is never revealed to someone who
     * does not already hold the credentials. Returning `null` instead hands the
     * attempt back to Fortify, which fires `Failed`, counts it against the
     * throttle and shows the generic message.
     *
     * @throws ValidationException
     */
    private function authenticate(Request $request): ?User
    {
        $login = (string) $request->input(Fortify::username());

        $user = User::query()
            ->where(fn ($query) => $query->where('email', $login)->orWhere('username', $login))
            ->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return null;
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                Fortify::username() => __('This account has been deactivated. Contact an administrator.'),
            ]);
        }

        return $user;
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
