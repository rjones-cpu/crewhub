import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-slate-100 px-4 py-10">
            <div className="mb-8 flex flex-col items-center text-center">
                <Link href="/" className="flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-sidebar text-white shadow-sm">
                        <span className="text-sm font-bold tracking-tight">CX</span>
                    </div>
                    <div className="text-left">
                        <p className="text-xl font-semibold tracking-tight text-slate-900">Crew Hub</p>
                        <p className="text-sm text-slate-500">Workforce command center</p>
                    </div>
                </Link>
            </div>

            <div className="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white px-6 py-6 shadow-card">
                {children}
            </div>
        </div>
    );
}
