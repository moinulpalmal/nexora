<?php

namespace App\Providers;

use App\Models\Admin\BrandingSetting;
use App\Models\Admin\Company;
use App\Models\Admin\MailSetting;
use App\Models\Admin\Permission;
use App\Models\Admin\QuickLoginSetting;
use App\Models\Admin\Role;
use App\Models\Hrm\Department;
use App\Models\ImportLimit;
use App\Models\SheetImport;
use App\Models\User;
use App\Services\Admin\MailSettingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\DevCommands;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Mailer\Transport\TransportInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Every model that may appear in a polymorphic column, by its stored alias.
     *
     * Aliases are kebab-case singular, matching the URL vocabulary rather than
     * the class name — the point is that the stored value does not track the
     * class. **This map is also the audit viewer's model allow-list**, so the
     * list of auditable types can never drift from what is actually mapped.
     *
     * @var array<string, class-string<Model>>
     */
    public const array MORPH_MAP = [
        'user' => User::class,
        'role' => Role::class,
        'company' => Company::class,
        'department' => Department::class,
        'permission' => Permission::class,
        'mail-setting' => MailSetting::class,
        'quick-login-setting' => QuickLoginSetting::class,
        'branding-setting' => BrandingSetting::class,
        'sheet-import' => SheetImport::class,
        'import-limit' => ImportLimit::class,
    ];

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
        $this->configureMorphMap();
        $this->configureAuthorization();
        $this->configureDevCommands();
        $this->configureMailer();
    }

    /**
     * Store polymorphic types as short aliases rather than class names.
     *
     * `audit_logs.auditable_type` is the reason this exists: without a map,
     * moving or renaming a model orphans its entire history.
     *
     * **`enforceMorphMap`, not `morphMap`.** The enforcing variant throws when an
     * unmapped model is used in a morph, so a model added later fails loudly on
     * its first audit instead of quietly writing a class name a future rename
     * would orphan. Adding a model to {@see self::MORPH_MAP} is part of adding a
     * model.
     *
     * The map is global: spatie/laravel-permission writes
     * `model_has_roles.model_type` through the same `getMorphClass()`, so it was
     * registered before any role was ever assigned.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap(self::MORPH_MAP);
    }

    /**
     * Grant the super-admin role every ability.
     *
     * Returning null rather than false leaves every other check untouched, so
     * a normal user still falls through to the permission check. Because this
     * bypass wins over everything, self-protection rules ("you cannot delete
     * yourself", "the last super admin stays") live in the services as
     * `*Blocker()` guards, not in gates — see ARCHITECTURE.md §9.1.
     *
     * @see Role::SUPER_ADMIN
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole(Role::SUPER_ADMIN) ? true : null);
    }

    /**
     * Processes `php artisan dev` runs alongside the framework defaults
     * (server, default queue, logs, vite).
     *
     * Mail has its own queue so a slow SMTP server never delays other jobs.
     */
    protected function configureDevCommands(): void
    {
        DevCommands::artisan('queue:listen --queue=mail --tries=3 --timeout=0', 'mail');
        DevCommands::artisan('schedule:work', 'schedule');
    }

    /**
     * Register the `database` mailer, whose transport is built from the mail
     * server saved on Application Settings → Mail Server.
     *
     * Registered after the mail manager resolves, so a request that sends no
     * mail never reads the setting.
     */
    protected function configureMailer(): void
    {
        $this->callAfterResolving('mail.manager', function (MailManager $manager): void {
            $manager->extend(
                MailSettingService::MAILER,
                fn (): TransportInterface => $this->app->make(MailSettingService::class)->transport($manager),
            );
        });
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
