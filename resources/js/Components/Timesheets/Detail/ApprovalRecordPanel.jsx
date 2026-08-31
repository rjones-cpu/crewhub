import { Check } from 'lucide-react';
import { cn } from '@/utils/helpers';

const STATES = {
    completed: { marker: 'bg-success text-white', badge: 'bg-success-soft text-success', label: 'Approved' },
    confirmed: { marker: 'bg-brand text-white', badge: 'bg-brand-soft text-brand', label: 'Confirmed' },
    in_progress: { marker: 'bg-brand text-white', badge: 'bg-brand-soft text-brand', label: 'In Progress' },
    pending: { marker: 'bg-slate-200 text-slate-500', badge: 'bg-slate-100 text-slate-500', label: 'Pending' },
    not_required: {
        marker: 'bg-slate-200 text-slate-500',
        badge: 'bg-slate-100 text-slate-500',
        label: 'Not Required',
    },
};

const LEGEND = [
    ['Completed', 'bg-success'],
    ['In Progress', 'bg-brand'],
    ['Pending', 'bg-slate-300'],
];

const SHOWS_TICK = ['completed', 'confirmed'];

export default function ApprovalRecordPanel({ record = [] }) {
    return (
        <div className="card card-padding">
            <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                Approval Record
            </h2>
            <p className="mt-1 text-[11px] text-slate-500">
                Approval history and status for this timesheet.
            </p>

            <ol className="mt-4 space-y-1">
                {record.map((step, index) => {
                    const state = STATES[step.state] || STATES.pending;
                    const last = index === record.length - 1;

                    return (
                        <li key={step.key} className="flex gap-3">
                            <div className="flex flex-col items-center">
                                <span
                                    className={cn(
                                        'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold',
                                        state.marker,
                                    )}
                                >
                                    {SHOWS_TICK.includes(step.state) ? (
                                        <Check className="h-3.5 w-3.5" />
                                    ) : (
                                        index + 1
                                    )}
                                </span>
                                {!last && <span className="mt-1 w-px flex-1 bg-slate-200" />}
                            </div>

                            <div className={cn('min-w-0 flex-1', !last && 'pb-4')}>
                                <div className="flex items-start justify-between gap-2">
                                    <p className="text-xs font-medium text-slate-900">{step.title}</p>
                                    {step.at && (
                                        <p className="shrink-0 text-right text-[10px] leading-tight text-slate-400">
                                            {step.at}
                                        </p>
                                    )}
                                </div>
                                {step.actor && (
                                    <p className="text-[11px] text-slate-500">{step.actor}</p>
                                )}
                                {step.detail && (
                                    <p className="text-[11px] text-slate-500">{step.detail}</p>
                                )}
                                <span className={cn('badge mt-1.5 text-[10px]', state.badge)}>
                                    {SHOWS_TICK.includes(step.state) && (
                                        <Check className="mr-1 h-3 w-3" />
                                    )}
                                    {state.label}
                                </span>
                            </div>
                        </li>
                    );
                })}
            </ol>

            <ul className="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-3">
                {LEGEND.map(([label, dot]) => (
                    <li key={label} className="flex items-center gap-1.5 text-[11px] text-slate-600">
                        <span className={cn('h-2 w-2 rounded-full', dot)} />
                        {label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
