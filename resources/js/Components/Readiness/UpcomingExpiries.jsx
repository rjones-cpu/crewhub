import { BadgeCheck, CalendarClock, Stethoscope } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate } from '@/utils/formatters';

export default function UpcomingExpiries({ expiries = [] }) {
    return (
        <Card padding={false} className="rounded-lg p-3">
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-[10px] font-bold text-slate-800">Upcoming Expiries</h3>
                <span className="text-[7px] font-semibold text-brand">View all</span>
            </div>
            {expiries.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No upcoming expiries"
                    description="Certificates and documents nearing expiry will show here."
                />
            ) : (
                <ul className="divide-y divide-slate-100">
                    {expiries.map((item) => (
                        <li key={item.id} className="flex items-center justify-between gap-2 py-1.5">
                            <div className="flex min-w-0 items-center gap-2">
                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-slate-50 text-slate-500">
                                    {item.type === 'Medical' ? (
                                        <Stethoscope className="h-3 w-3" />
                                    ) : (
                                        <BadgeCheck className="h-3 w-3" />
                                    )}
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate text-[7px] font-semibold text-slate-700">{item.name}</p>
                                    <p className="truncate text-[7px] text-slate-400">{item.worker}</p>
                                </div>
                            </div>
                            <div className="shrink-0 text-right">
                                <p className={`text-[7px] font-semibold ${item.days_left <= 14 ? 'text-danger' : 'text-warning'}`}>
                                    {formatDate(item.expires_at)}
                                </p>
                                <p className="flex items-center justify-end gap-0.5 text-[7px] text-slate-400">
                                    <CalendarClock className="h-2.5 w-2.5" />
                                    {item.days_left} days
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
