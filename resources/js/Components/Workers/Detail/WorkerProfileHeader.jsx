import { Link } from '@inertiajs/react';
import { ChevronRight, HardHat, UserRound } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';

export default function WorkerProfileHeader({ worker, actions }) {
    return (
        <div className="space-y-3">
            <nav aria-label="Breadcrumb" className="flex items-center gap-1.5 text-[11px] text-slate-500">
                <Link href={route('workers.index')} className="hover:text-slate-700">
                    Workers
                </Link>
                <ChevronRight className="h-3 w-3 text-slate-300" />
                <span className="font-semibold text-slate-900">{worker.full_name}</span>
            </nav>

            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex min-w-0 items-start gap-3">
                    <Avatar
                        name={worker.full_name}
                        src={worker.avatar}
                        size="lg"
                        className="h-16 w-16 ring-2 ring-white"
                    />
                    <div className="min-w-0 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="truncate text-lg font-semibold text-slate-900">{worker.full_name}</h2>
                            <Badge status={worker.status} className="px-2 py-0.5 text-[9px]" />
                            {worker.on_site && (
                                <Badge tone="brand" className="px-2 py-0.5 text-[9px]">
                                    On site
                                </Badge>
                            )}
                        </div>

                        <p className="flex items-center gap-1.5 text-[11px] text-slate-600">
                            <HardHat className="h-3.5 w-3.5 text-slate-400" />
                            {worker.position || 'No position on record'}
                        </p>

                        <dl className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600">
                            <div className="flex gap-1">
                                <dt className="font-semibold text-slate-700">Employee ID:</dt>
                                <dd>{worker.employee_id || '—'}</dd>
                            </div>
                            <span className="text-slate-300">|</span>
                            <div className="flex gap-1">
                                <dt className="font-semibold text-slate-700">Email:</dt>
                                <dd className="truncate">{worker.email || '—'}</dd>
                            </div>
                            <span className="text-slate-300">|</span>
                            <div className="flex gap-1">
                                <dt className="font-semibold text-slate-700">Phone:</dt>
                                <dd>{worker.phone || '—'}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <Link
                        href={route('workers.edit', worker.id)}
                        className="inline-flex h-8 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 text-[10px] font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <UserRound className="h-3.5 w-3.5" />
                        View Worker Profile
                    </Link>
                    {actions}
                </div>
            </div>
        </div>
    );
}
