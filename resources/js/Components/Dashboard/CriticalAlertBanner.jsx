import { Link } from '@inertiajs/react';
import { AlertCircle, ArrowRight } from 'lucide-react';

export default function CriticalAlertBanner({ count = 0, href }) {
    if (count < 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-3 rounded-xl border border-danger/20 bg-danger-soft px-4 py-2.5">
            <div className="flex items-center gap-2.5">
                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-danger text-white">
                    <AlertCircle className="h-4 w-4" />
                </span>
                <div>
                    <p className="text-[11px] font-semibold text-slate-900">
                        {count} {count === 1 ? 'Project Needs' : 'Projects Need'} Attention
                    </p>
                    <p className="text-[10px] text-slate-600">
                        Projects with critical or declining scores require your immediate attention.
                    </p>
                </div>
            </div>

            <Link
                href={href}
                className="inline-flex shrink-0 items-center gap-1 rounded-lg bg-danger px-3 py-1.5 text-[10px] font-medium text-white transition hover:bg-danger/90"
            >
                View Critical Items
                <ArrowRight className="h-3 w-3" />
            </Link>
        </div>
    );
}
