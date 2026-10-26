import { Head, usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="card border border-base-300 bg-base-100">
                    <div className="card-body">
                        <h1 className="card-title">
                            Welcome back, {auth.user.name}
                        </h1>
                        <p className="text-base-content/70">
                            Modules you have access to appear in the sidebar.
                        </p>
                    </div>
                </div>

                <div className="flex flex-1 flex-col items-center justify-center gap-2 rounded-box border border-dashed border-base-300 p-12 text-base-content/50">
                    <LayoutGrid className="size-8" />
                    <span className="text-sm">Dashboard widgets go here.</span>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
