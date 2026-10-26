import { Button } from '@/components/ui/button';

export type QuickLoginAccount = {
    label: string;
    username: string;
};

type Props = {
    /** Empty unless Quick Login is on AND the environment is local/testing. */
    accounts: QuickLoginAccount[];
    onSelect: (username: string) => void;
};

/**
 * One-click sign-in as each seeded role, for testing permission-gated screens.
 * Fills the login form and submits it through the real Fortify login — there
 * is no auth bypass here. The accounts come from the server only while both
 * locks are open (`QuickLoginService`), so an install with it off carries no
 * account name in the page.
 */
export default function DevQuickLogin({ accounts, onSelect }: Props) {
    return (
        <div className="mt-8 rounded-box border-2 border-dashed border-warning p-4">
            <p className="mb-3 text-center text-sm font-bold text-warning">
                QUICK LOGIN — TESTING ONLY
            </p>

            <div className="grid grid-cols-2 gap-2">
                {accounts.map((account) => (
                    <Button
                        key={account.username}
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onSelect(account.username)}
                        data-test={`quick-login-${account.username}`}
                    >
                        {account.label}
                    </Button>
                ))}
            </div>
        </div>
    );
}
