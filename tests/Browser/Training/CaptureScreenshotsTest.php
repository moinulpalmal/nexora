<?php

use App\Enums\Admin\MailEncryption;
use App\Enums\Admin\MailTransport;
use App\Listeners\Admin\RecordSentMail;
use App\Mail\Admin\MailSettingTestMail;
use App\Mail\Admin\UserPasswordResetMail;
use App\Mail\Admin\UserWelcomeMail;
use App\Models\Admin\AuditLog;
use App\Models\Admin\Company;
use App\Models\Admin\MailSetting;
use App\Models\Admin\QuickLoginSetting;
use App\Models\Admin\Role;
use App\Models\Admin\SentMail;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\User;
use App\Notifications\Admin\AccountChanged;
use App\Services\Training\TrainingRegistry;
use Database\Seeders\Admin\DemoUserSeeder;
use Database\Seeders\Admin\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Pest\Browser\Support\Screenshot;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Training — capturing every guide's screenshots
|--------------------------------------------------------------------------
|
| Not a test of behaviour: the recipe for every screenshot a written guide
| declares in config/training.php, run with `composer training:screens`, which
| writes them to public/images/training/{key}/{shot}.png (see
| documentation/training.md §6).
|
| Skipped unless TRAINING_SCREENS=1, so the ordinary browser run never
| rewrites the committed images. Every person, address and record below is
| invented: training is open to every user, and a screenshot of real rows
| would show them people and settings they have no right to see.
|
| Light theme, 1440 × 900, the viewport only — never the full page.
|
*/

pest()->group('training-screens');

beforeEach(function (): void {
    if (! env('TRAINING_SCREENS')) {
        $this->markTestSkipped('Screenshots are captured by `composer training:screens` only.');
    }

    $this->seed(RolePermissionSeeder::class);
});

/**
 * Save the viewport as one of a guide's screenshots.
 */
function captureTrainingShot(mixed $page, string $key, string $shot): void
{
    expect(TrainingRegistry::get($key)['shots'] ?? [])->toHaveKey($shot);

    $temporary = 'training-'.str_replace('.', '-', $key).'-'.$shot;
    $page->screenshot(false, $temporary);

    $target = TrainingRegistry::shotPath($key, $shot);
    File::ensureDirectoryExists(dirname($target));
    File::move(Screenshot::path($temporary), $target);
}

/**
 * Open a screen the way a guide's reader would see it.
 */
function visitForTraining(string $url, int $width = 1440, int $height = 900): mixed
{
    return visit($url)->inLightMode()->resize($width, $height);
}

/**
 * The reader: an invented super admin, so every Admin screen and button is
 * in the picture.
 */
function trainingActor(): User
{
    $actor = superAdmin();
    $actor->update(['name' => 'Farhana Akter', 'username' => 'farhana', 'email' => 'farhana.akter@example.com', 'employee_code' => 'E-1001']);

    test()->actingAs($actor);

    return $actor;
}

/**
 * A few invented colleagues, each holding a seeded role.
 *
 * @return array<string, User>
 */
function trainingColleagues(): array
{
    $people = [
        'ayesha' => ['Ayesha Rahman', 'E-1002', '01712-345678', 'admin'],
        'tanvir' => ['Tanvir Hossain', 'E-1003', '01812-223344', 'viewer'],
        'nadia' => ['Nadia Karim', 'E-1004', null, 'viewer'],
    ];

    $users = [];

    foreach ($people as $username => [$name, $code, $phone, $role]) {
        $users[$username] = User::factory()->create([
            'name' => $name,
            'username' => $username,
            'email' => "{$username}@example.com",
            'employee_code' => $code,
            'phone' => $phone,
        ]);
        $users[$username]->assignRole($role);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $users;
}

/**
 * Invented group companies, keyed by short code in lower case.
 *
 * @return array<string, Company>
 */
function trainingCompanies(): array
{
    $companies = [];

    foreach ([['Palmal Garments Ltd.', 'PGL'], ['Palmal Knit Composite Ltd.', 'PKC'], ['Palmal Packaging Ltd.', 'PPL', 'I']] as $row) {
        $companies[strtolower($row[1])] = Company::factory()->create([
            'name' => $row[0],
            'short_code' => $row[1],
            'status' => $row[2] ?? 'A',
        ]);
    }

    return $companies;
}

test('dashboard', function () {
    $actor = trainingActor();
    $actor->notify(new AccountChanged('Your roles were changed', 'You now hold: super-admin.'));

    $page = visitForTraining(route('dashboard', absolute: false))->assertSee('Welcome back, Farhana Akter');
    captureTrainingShot($page, 'dashboard', 'overview');

    $page->click('[data-test="notification-bell"]')->assertVisible('[data-test="notification-item"]');
    captureTrainingShot($page, 'dashboard', 'bell');
});

test('admin.users', function () {
    $key = 'admin.users';
    trainingActor();
    $colleagues = trainingColleagues();
    $colleagues['nadia']->update(['status' => 'I']);

    $leaver = User::factory()->create(['name' => 'Rafiq Islam', 'username' => 'rafiq', 'email' => 'rafiq@example.com', 'employee_code' => 'E-0990']);
    $leaver->delete();

    // Wider than the usual 1440, so the row actions are not cut off.
    $page = visitForTraining(route('admin.users.index', absolute: false), 1600)->assertSee('Ayesha Rahman');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[data-test="new-user"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'form');

    $page = visitForTraining(route('admin.users.index', absolute: false), 1600)
        ->click('[aria-label="Roles for Tanvir Hossain"]')
        ->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'roles');

    trainingCompanies();
    $page = visitForTraining(route('admin.users.index', absolute: false), 1600)
        ->click('[aria-label="Companies for Tanvir Hossain"]')
        ->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'companies');

    $page = visitForTraining(route('admin.users.index', absolute: false), 1600)
        ->click('[aria-label="Set password for Tanvir Hossain"]')
        ->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'password');

    $page = visitForTraining(route('admin.users.index', ['view' => 'trashed'], absolute: false), 1600)->assertSee('Rafiq Islam');
    captureTrainingShot($page, $key, 'deleted');
});

test('admin.companies', function () {
    $key = 'admin.companies';
    trainingActor();
    trainingCompanies();
    Company::factory()->create(['name' => 'Palmal Washing Ltd.', 'short_code' => 'PWL'])->delete();

    $page = visitForTraining(route('admin.companies.index', absolute: false))->assertSee('Palmal Garments Ltd.');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[data-test="new-company"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'form');

    $page = visitForTraining(route('admin.companies.index', ['view' => 'trashed'], absolute: false))->assertSee('Palmal Washing Ltd.');
    captureTrainingShot($page, $key, 'deleted');
});

test('admin.roles', function () {
    $key = 'admin.roles';
    trainingActor();
    trainingColleagues();
    Role::findOrCreate('data-entry', 'web')->givePermissionTo('admin.users.view', 'admin.users.create', 'admin.users.update');

    $page = visitForTraining(route('admin.roles.index', absolute: false))->assertSee('data-entry');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[data-test="new-role"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'form');
});

test('hrm.departments', function () {
    $key = 'hrm.departments';
    $actor = trainingActor();
    $companies = trainingCompanies();
    $actor->forceFill(['selected_company_id' => $companies['pgl']->id])->saveQuietly();

    foreach ([['HR', 'Human Resources'], ['PROD', 'Production'], ['FIN', 'Finance & Accounts'], ['QA', 'Quality Assurance'], ['MERCH', 'Merchandising']] as [$code, $name]) {
        Department::factory()->for($companies['pgl'])->create(['code' => $code, 'name' => $name]);
    }

    $page = visitForTraining(route('hrm.departments.index', absolute: false))->assertSee('Human Resources');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[data-test="new-department"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'form');

    $page = visitForTraining(route('hrm.departments.index', absolute: false))
        ->click('[data-test="import-departments"]')
        ->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'import');
});

test('hrm.designations', function () {
    $key = 'hrm.designations';
    trainingActor();

    foreach ([['OP', 'Operator'], ['SR-OP', 'Senior Operator'], ['SUP', 'Supervisor'], ['QI', 'Quality Inspector'], ['MGR', 'Production Manager']] as [$code, $name]) {
        Designation::factory()->create(['code' => $code, 'name' => $name]);
    }

    $page = visitForTraining(route('hrm.designations.index', absolute: false))->assertSee('Senior Operator');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[data-test="new-designation"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'form');

    $page = visitForTraining(route('hrm.designations.index', absolute: false))
        ->click('[data-test="import-designations"]')
        ->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'import');
});

test('admin.permissions', function () {
    trainingActor();

    $page = visitForTraining(route('admin.permissions.index', absolute: false))->assertSee('admin.users.view');
    captureTrainingShot($page, 'admin.permissions', 'overview');
});

test('admin.audit-logs', function () {
    $key = 'admin.audit-logs';
    trainingActor();
    $colleagues = trainingColleagues();

    // Console writes are not audited (ARCHITECTURE.md §9.3); this recipe needs rows.
    config(['audit.console' => true]);
    $colleagues['nadia']->update(['status' => 'I']);
    $colleagues['tanvir']->update(['phone' => '01812-998877', 'employee_code' => 'E-1013']);
    config(['audit.console' => false]);

    // A console write records the command line; show what a screen edit records.
    foreach (AuditLog::query()->get() as $audit) {
        $audit->forceFill([
            'url' => "https://nexora.example.com/admin/users/{$audit->auditable_id}",
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36',
        ])->saveQuietly();
    }

    $page = visitForTraining(route('admin.audit-logs.index', absolute: false))->assertSee('Farhana Akter');
    captureTrainingShot($page, $key, 'overview');

    $page->click('tbody tr:first-child [data-test="view-audit"]')->assertVisible('dialog[open]');
    captureTrainingShot($page, $key, 'detail');

    $page = visitForTraining(route('admin.audit-logs.index', absolute: false))
        ->click('tbody tr:first-child [data-test="view-audit-history"]')
        ->assertVisible('dialog[open]')
        ->assertDontSee('Loading');
    captureTrainingShot($page, $key, 'history');
});

test('admin.sent-mails', function () {
    $key = 'admin.sent-mails';
    $actor = trainingActor();
    $colleagues = trainingColleagues();

    $mails = [
        [$colleagues['nadia'], new UserWelcomeMail($colleagues['nadia'], RecordSentMail::MASK), 3],
        [$colleagues['tanvir'], new UserPasswordResetMail($colleagues['tanvir'], RecordSentMail::MASK, $actor->name), 2],
        [$actor, new MailSettingTestMail($actor), 1],
        [$colleagues['ayesha'], new UserWelcomeMail($colleagues['ayesha'], RecordSentMail::MASK), 0],
    ];

    // The stored copies link to an invented address, not the test server's port.
    URL::forceRootUrl('https://nexora.example.com');

    foreach ($mails as [$recipient, $mail, $hoursAgo]) {
        SentMail::query()->create([
            'mailable' => class_basename($mail),
            'to_address' => $recipient->email,
            'subject' => $mail->envelope()->subject,
            'html' => $mail->render(),
            'user_id' => $recipient->id,
            'mailer' => 'smtp',
            'sent_at' => now()->subHours($hoursAgo),
        ]);
    }

    URL::forceRootUrl(null);

    $page = visitForTraining(route('admin.sent-mails.index', absolute: false))->assertSee('ayesha@example.com');
    captureTrainingShot($page, $key, 'overview');

    $page->click('[aria-label="Preview the mail to ayesha@example.com"]')->assertVisible('[data-test="sent-mail-frame"]');
    captureTrainingShot($page, $key, 'mail');
});

test('admin.application-settings', function () {
    $key = 'admin.application-settings';
    trainingActor();

    $settings = MailSetting::current();
    $settings->id = MailSetting::SINGLETON_ID;
    $settings->fill([
        'transport' => MailTransport::Smtp,
        'host' => 'mail.example.com',
        'port' => 465,
        'encryption' => MailEncryption::Ssl,
        'username' => 'nexora@example.com',
        'password' => 'invented',
        'from_address' => 'nexora@example.com',
        'from_name' => 'Nexora',
    ])->save();

    // Taller than the usual 900, so Send test mail is in the picture.
    $page = visitForTraining(route('admin.application-settings.edit', ['tab' => 'mail'], absolute: false), height: 1080)
        ->assertVisible('[data-test="send-test-mail"]');
    captureTrainingShot($page, $key, 'overview');

    // The demo accounts the buttons sign in as, so the table reads "Active".
    User::factory()->create(['name' => 'Super Admin', 'username' => mb_strtolower((string) config('auth.super_admin.username'))]);

    foreach (DemoUserSeeder::ACCOUNTS as $username => $account) {
        User::factory()->create(['name' => $account['name'], 'username' => $username]);
    }

    $quickLogin = QuickLoginSetting::current();
    $quickLogin->id = QuickLoginSetting::SINGLETON_ID;
    $quickLogin->fill(['enabled' => true])->save();

    $page = visitForTraining(route('admin.application-settings.edit', ['tab' => 'quick-login'], absolute: false))
        ->assertVisible('[data-test="quick-login-enabled"]');
    captureTrainingShot($page, $key, 'quick-login');
});

test('profile.edit', function () {
    $key = 'profile.edit';
    $this->actingAs(User::factory()->create([
        'name' => 'Ayesha Rahman',
        'username' => 'ayesha',
        'email' => 'ayesha@example.com',
        'phone' => '01712-345678',
        'employee_code' => 'E-1002',
    ]));

    $page = visitForTraining(route('profile.edit', absolute: false))->assertSee('Your employee code and roles are managed by');
    captureTrainingShot($page, $key, 'overview');

    $page->clear('#username')
        ->type('#username', 'ayesha.rahman')
        ->assertVisible('[data-test="profile-current-password"]');
    captureTrainingShot($page, $key, 'sign-in-change');
});

test('security.edit', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ayesha Rahman']));

    // The page asks for the password again first; the factory's is "password".
    $page = visitForTraining(route('security.edit', absolute: false))
        ->type('password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertSee('Update password');
    captureTrainingShot($page, 'security.edit', 'overview');
});

test('appearance.edit', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ayesha Rahman']));

    $page = visitForTraining(route('appearance.edit', absolute: false))->assertSee('Theme');
    captureTrainingShot($page, 'appearance.edit', 'overview');
});
