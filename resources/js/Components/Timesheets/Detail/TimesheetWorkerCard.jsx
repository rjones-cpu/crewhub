import { CalendarDays } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import { formatDate } from '@/utils/formatters';

function Detail({ label, children }) {
    return (
        <div className="min-w-0">
            <p className="text-[11px] text-slate-500">{label}</p>
            <div className="mt-1 text-xs font-medium text-slate-900">{children}</div>
        </div>
    );
}

export default function TimesheetWorkerCard({ timesheet }) {
    const worker = timesheet.worker || {};
    const periodStart = formatDate(timesheet.period_start, { month: 'short', day: 'numeric', year: 'numeric' });
    const periodEnd = formatDate(timesheet.period_end, { month: 'short', day: 'numeric', year: 'numeric' });

    return (
        <div className="card card-padding">
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div className="flex items-center gap-3">
                    <Avatar name={worker.full_name} src={worker.avatar} size="lg" />
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-slate-900">
                            {worker.full_name}
                        </p>
                        <p className="truncate text-xs text-slate-600">{worker.position || '—'}</p>
                        <p className="truncate text-[11px] text-slate-400">{worker.company || '—'}</p>
                    </div>
                </div>

                <Detail label="Week:">
                    <span className="flex items-start justify-between gap-2">
                        <span>
                            <span className="block">
                                {timesheet.week_number ? `Week ${timesheet.week_number}` : '—'}
                            </span>
                            <span className="block text-[11px] font-normal text-slate-500">
                                {periodStart} – {periodEnd}
                            </span>
                        </span>
                        <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                    </span>
                </Detail>

                <Detail label="Employee ID">{worker.employee_id || '—'}</Detail>

                <Detail label="Work Location">
                    <span className="block">{timesheet.project?.name || '—'}</span>
                    {worker.location && (
                        <span className="block text-[11px] font-normal text-slate-500">
                            {worker.location}
                        </span>
                    )}
                </Detail>
            </div>
        </div>
    );
}
