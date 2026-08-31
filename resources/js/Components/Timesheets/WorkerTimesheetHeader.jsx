import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import { formatDate } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

export default function WorkerTimesheetHeader({ timesheet, onToggleClientApproval, editable }) {
    const worker = timesheet.worker || {};
    const project = timesheet.project || {};

    return (
        <Card>
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-lg font-semibold text-slate-900">{worker.full_name || 'Worker'}</h2>
                        <Badge status={worker.status || 'active'} />
                    </div>
                    <p className="mt-1 text-sm text-slate-500">ID {worker.employee_id || '—'}</p>
                </div>
                <Badge status={timesheet.status}>{timesheet.status_label}</Badge>
            </div>

            <dl className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 text-sm">
                {[
                    ['Company', worker.company || '—'],
                    ['Major Project', project.name || '—'],
                    ['Position', worker.position || '—'],
                    ['Supervisor', timesheet.supervisor_name || '—'],
                    ['Week Ending', formatDate(timesheet.period_end, { month: 'short', day: 'numeric' })],
                    [
                        'Payroll Period',
                        `${formatDate(timesheet.period_start, { month: 'short', day: 'numeric' })} – ${formatDate(timesheet.period_end, { month: 'short', day: 'numeric' })}`,
                    ],
                    ['Approval Path', timesheet.client_approval_required ? 'Worker → Manager → Client' : 'Worker → Manager'],
                ].map(([label, value]) => (
                    <div key={label}>
                        <dt className="text-slate-500">{label}</dt>
                        <dd className="mt-1 font-medium text-slate-900">{value}</dd>
                    </div>
                ))}
                <div>
                    <dt className="text-slate-500">Client Approval Required</dt>
                    <dd className="mt-2">
                        <button
                            type="button"
                            role="switch"
                            aria-checked={timesheet.client_approval_required}
                            disabled={!editable}
                            onClick={() => onToggleClientApproval?.(!timesheet.client_approval_required)}
                            className={cn(
                                'relative inline-flex h-6 w-11 shrink-0 rounded-full transition disabled:opacity-50',
                                timesheet.client_approval_required ? 'bg-brand' : 'bg-slate-200',
                            )}
                        >
                            <span
                                className={cn(
                                    'absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition',
                                    timesheet.client_approval_required && 'translate-x-5',
                                )}
                            />
                        </button>
                    </dd>
                </div>
            </dl>
        </Card>
    );
}
