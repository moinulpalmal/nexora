<?php

namespace App\Enums\Admin;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;

/**
 * The vocabulary of the audit trail's `event` column.
 *
 * **The first four are a contract with `config/audit.php`.** The package writes
 * those raw strings itself, so a case renamed here without renaming it there
 * produces a filter option that matches no row.
 *
 * The rest are this application's own, written through
 * `Admin\AuditRecorder` for what Eloquent events cannot see: pivot grants, mail
 * sent on a user's behalf, and authentication. Each names *what happened*
 * rather than which table moved, because the row already carries the table.
 *
 * Kebab-case values, matching the permission and route vocabulary.
 */
enum AuditEvent: string implements LabelledEnum
{
    use ProvidesOptions;

    /*
    | Written by the package
    */

    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';

    /*
    | Written by Admin\AuditRecorder
    */

    /** Roles granted to or revoked from a user. */
    case RolesChanged = 'roles-changed';

    /** Companies granted to or revoked from a user (ARCHITECTURE.md §9.10). */
    case CompaniesChanged = 'companies-changed';

    /** Permissions attached to or detached from a role. */
    case PermissionsChanged = 'permissions-changed';

    /** An administrator set another user's password. */
    case PasswordReset = 'password-reset';

    /** A user was permanently deleted; the row keeps what they were. */
    case ForceDeleted = 'force-deleted';

    /** The sign-in details mail sent to a new user. */
    case UserWelcomeMailed = 'user-welcome-mailed';

    /** The mail sent after an administrator reset a user's password. */
    case UserPasswordResetMailed = 'user-password-reset-mailed';

    /** The SMTP password was replaced; the values never record it. */
    case MailPasswordChanged = 'mail-password-changed';

    case MailTestSent = 'mail-test-sent';

    case LoggedIn = 'logged-in';
    case LoggedOut = 'logged-out';

    /** A rejected sign-in attempt. Records the login typed, never the password. */
    case LoginFailed = 'login-failed';

    public static function id(): string
    {
        return 'audit-event';
    }

    /**
     * A human label for the event.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Restored => 'Restored',
            self::RolesChanged => 'Roles changed',
            self::CompaniesChanged => 'Companies changed',
            self::PermissionsChanged => 'Permissions changed',
            self::PasswordReset => 'Password reset by an administrator',
            self::ForceDeleted => 'Permanently deleted',
            self::UserWelcomeMailed => 'Welcome mail sent',
            self::UserPasswordResetMailed => 'Password reset mail sent',
            self::MailPasswordChanged => 'Mail server password changed',
            self::MailTestSent => 'Test mail sent',
            self::LoggedIn => 'Signed in',
            self::LoggedOut => 'Signed out',
            self::LoginFailed => 'Sign-in failed',
        };
    }
}
