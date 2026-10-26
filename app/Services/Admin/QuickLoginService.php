<?php

namespace App\Services\Admin;

use App\Models\Admin\QuickLoginSetting;
use App\Models\User;
use Database\Seeders\Admin\DemoUserSeeder;

/**
 * One-click sign-in buttons on the login page, for testing permission-gated
 * screens as each seeded role.
 *
 * **Two locks, both required:** the switch on Application Settings → Quick
 * Login, *and* an environment of `local` or `testing`. Production can never
 * render them, whatever the switch says. The buttons fill the real login form
 * and submit it; there is no auth bypass.
 */
class QuickLoginService
{
    /**
     * The environments the buttons may ever appear in.
     *
     * @var list<string>
     */
    public const array ENVIRONMENTS = ['local', 'testing'];

    public function update(bool $enabled): QuickLoginSetting
    {
        $setting = QuickLoginSetting::query()->firstOrNew(['id' => QuickLoginSetting::SINGLETON_ID]);

        $setting->fill(['enabled' => $enabled])->save();

        return $setting;
    }

    public function environmentAllows(): bool
    {
        return app()->environment(self::ENVIRONMENTS);
    }

    /**
     * The accounts the buttons offer — the seeded super admin and the demo
     * accounts — each with whether it exists and can sign in.
     *
     * @return list<array{label: string, username: string, exists: bool}>
     */
    public function accounts(): array
    {
        /** @var string $superAdmin */
        $superAdmin = config('auth.super_admin.username');

        $candidates = [
            ['label' => 'Super admin', 'username' => mb_strtolower($superAdmin)],
            ...array_map(
                fn (string $username, array $account): array => ['label' => $account['name'], 'username' => $username],
                array_keys(DemoUserSeeder::ACCOUNTS),
                DemoUserSeeder::ACCOUNTS,
            ),
        ];

        $active = User::query()
            ->active()
            ->whereIn('username', array_column($candidates, 'username'))
            ->pluck('username')
            ->all();

        return array_map(
            fn (array $account): array => [...$account, 'exists' => in_array($account['username'], $active, true)],
            $candidates,
        );
    }

    /**
     * What the login page renders: nothing unless both locks are open.
     *
     * @return list<array{label: string, username: string}>
     */
    public function loginAccounts(): array
    {
        if (! $this->environmentAllows() || ! QuickLoginSetting::current()->enabled) {
            return [];
        }

        return array_values(array_map(
            fn (array $account): array => ['label' => $account['label'], 'username' => $account['username']],
            array_filter($this->accounts(), fn (array $account): bool => $account['exists']),
        ));
    }
}
