import { Link } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatNumber } from '@/utils/formatters';
import CardFooterLink from './CardFooterLink';

export default function UpcomingMobilizationsCard({ mobilizations = [], href }) {
    return (
        <div className="card flex h-full flex-col p-4">
            <div className="flex items-center justify-between gap-2">
                <h3 className="section-title">Upcoming Mobilizations</h3>
                <Link
                    href={href}
                    className="text-[10px] font-medium text-brand transition hover:text-brand-hover"
                >
                    View all
                </Link>
            </div>

            {mobilizations.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No mobilizations scheduled"
                    description="Crew movements booked in the next 30 days appear here."
                />
            ) : (
                <ul className="mt-2 min-h-0 flex-1 divide-y divide-slate-100">
                    {mobilizations.map((item) => (
                        <li key={item.id} className="flex items-center gap-2 py-1.5">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-brand-soft text-brand">
                                <CalendarDays className="h-3.5 w-3.5" />
                            </span>
                            <span className="w-[42px] shrink-0 text-[10px] font-semibold text-slate-900">
                                {item.date}
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-[10px] font-medium text-slate-800">
                                    {item.project}
                                </p>
                                <p className="text-[9px] text-slate-500">
                                    {formatNumber(item.workers)} workers
                                </p>
                            </div>
                            <span className="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-medium text-success">
                                {item.status}
                            </span>
                        </li>
                    ))}
                </ul>
            )}

            <CardFooterLink href={href}>View all mobilizations</CardFooterLink>
        </div>
    );
}
