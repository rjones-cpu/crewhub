import {
    AlertTriangle,
    ArrowRight,
    ArrowUp,
    Building2,
    CalendarDays,
    Check,
    CircleCheck,
    Clock,
    Paperclip,
    X,
} from 'lucide-react';
import { useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import { cn } from '@/utils/helpers';
import {
    APPROVAL_STATE,
    impactTone,
    requestStatus,
    shiftStatus,
} from './scheduleDesign';

const TABS = [
    { key: 'timeline', label: 'Audit Timeline' },
    { key: 'conflicts', label: 'Conflicts & Alerts' },
    { key: 'context', label: 'Related Context' },
    { key: 'attachments', label: 'Attachments' },
];

function SectionHeading({ children }) {
    return (
        <h3 className="text-[9px] font-semibold uppercase tracking-wider text-slate-400">
            {children}
        </h3>
    );
}

function MatrixRow({ label, value, valueClassName = 'text-slate-700' }) {
    return (
        <div className="flex items-center justify-between gap-3 text-[10px]">
            <span className="text-slate-400">{label}</span>
            <span className={cn('font-medium', valueClassName)}>{value}</span>
        </div>
    );
}

function ReadinessList({ readiness = {} }) {
    const issues = Array.isArray(readiness.issues) ? readiness.issues : [];
    const conflicts = Array.isArray(readiness.conflicts) ? readiness.conflicts : [];

    if (issues.length === 0 && conflicts.length === 0) {
        return (
            <ul className="space-y-1">
                <li className="flex items-center gap-1.5 text-[10px] text-success">
                    <CircleCheck className="h-3.5 w-3.5 shrink-0" />
                    No readiness issues
                </li>
                <li className="flex items-center gap-1.5 text-[10px] text-success">
                    <CircleCheck className="h-3.5 w-3.5 shrink-0" />
                    No conflicts. Available for requested shift.
                </li>
            </ul>
        );
    }

    return (
        <ul className="space-y-1">
            {issues.map((issue) => (
                <li key={issue} className="flex items-start gap-1.5 text-[10px] text-danger">
                    <AlertTriangle className="mt-px h-3.5 w-3.5 shrink-0" />
                    {issue}
                </li>
            ))}
            {conflicts.map((conflict) => (
                <li key={conflict} className="flex items-start gap-1.5 text-[10px] text-danger">
                    <AlertTriangle className="mt-px h-3.5 w-3.5 shrink-0" />
                    {conflict}
                </li>
            ))}
        </ul>
    );
}

function varianceLabel(variance) {
    if (variance === 0 || variance === '0') {
        return { text: '0 (On Target)', className: 'text-success' };
    }

    const numeric = Number(variance);
    const signed = Number.isNaN(numeric)
        ? String(variance)
        : `${numeric > 0 ? '+' : ''}${numeric}`;

    return { text: signed, className: 'text-danger' };
}

export default function ChangeRequestDetailPanel({
    request,
    canApprove = false,
    disabledReason = 'Change request approvals are not connected yet',
    onClose = () => {},
    onAction = () => {},
}) {
    const [tab, setTab] = useState('timeline');

    if (!request) {
        return null;
    }

    const status = requestStatus(request.status);
    const impact = impactTone(request.impact?.value);
    const shift = shiftStatus(request.shift);
    const current = request.current_schedule || {};
    const requested = request.requested_schedule || request.requested_change || {};
    const coverage = request.coverage_impact || {};
    const forecast = request.position_forecast || {};
    const readiness = request.readiness || {};
    const chain = Array.isArray(request.approval_chain) ? request.approval_chain.slice(0, 3) : [];
    const timeline = Array.isArray(request.timeline) ? request.timeline : [];
    const attachmentCount = Number(request.attachments) || 0;
    const coveragePct = Number(coverage.coverage);
    const forecastVariance = varianceLabel(forecast.variance);
    const disabledTitle = canApprove ? undefined : disabledReason;

    return (
        <aside className="card flex h-full min-h-0 flex-col overflow-hidden rounded-xl">
            <div className="flex items-start justify-between gap-2 border-b border-slate-100 px-3 py-2.5">
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                    <h2 className="text-xs font-semibold text-slate-800">{request.id}</h2>
                    <span className={cn('badge !px-1.5 !py-0.5 !text-[10px]', status.className)}>
                        {status.label}
                    </span>
                </div>
                <button
                    type="button"
                    aria-label="Close request detail"
                    onClick={onClose}
                    className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                >
                    <X className="h-3.5 w-3.5" />
                </button>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto">
                <div className="flex items-start gap-2 border-b border-slate-100 px-3 py-2.5">
                    <Avatar name={request.worker} size="sm" className="h-8 w-8 text-[10px] ring-0" />
                    <div className="min-w-0">
                        <p className="truncate text-[11px] font-semibold text-slate-800">{request.worker}</p>
                        <p className="truncate text-[10px] text-slate-400">{request.position}</p>
                        <div className="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[10px] text-slate-500">
                            <span className="inline-flex items-center gap-1">
                                <Building2 className="h-3 w-3 text-slate-400" />
                                {request.department}
                            </span>
                            <span className="inline-flex items-center gap-1">
                                <Clock className="h-3 w-3 text-slate-400" />
                                {current.shift || request.date_shift?.shift}
                            </span>
                            <span className="inline-flex items-center gap-1">
                                <CalendarDays className="h-3 w-3 text-slate-400" />
                                {current.date}
                                {current.time ? ` · ${current.time}` : ''}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-3 border-b border-slate-100 px-3 py-2.5">
                    <div>
                        <SectionHeading>Current Schedule</SectionHeading>
                        <p className="mt-1 text-[10px] text-slate-600">{current.date}</p>
                        <span className={cn('mt-0.5 inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-medium', shift.soft)}>
                            {current.shift}
                        </span>
                        <p className="mt-0.5 text-[10px] text-slate-400">{current.time}</p>
                    </div>
                    <div>
                        <SectionHeading>Requested Change</SectionHeading>
                        <p className="mt-1 text-[10px] font-medium text-slate-700">{requested.date}</p>
                        <p className="mt-0.5 inline-flex items-center gap-1 text-[10px] text-brand">
                            <ArrowRight className="h-3 w-3 shrink-0" />
                            {requested.time || requested.detail}
                        </p>
                        {requested.note && (
                            <p className="mt-0.5 text-[10px] text-slate-400">{requested.note}</p>
                        )}
                    </div>
                    <div>
                        <SectionHeading>Reason</SectionHeading>
                        <p className="mt-1 text-[10px] leading-snug text-slate-600">{request.reason}</p>
                    </div>
                </div>

                <div className="border-b border-slate-100 px-3 py-2.5">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <SectionHeading>Operational Impact</SectionHeading>
                            <p className="mt-1 text-[10px] leading-snug text-slate-600">
                                {request.operational_impact}
                            </p>
                        </div>
                        <span
                            className={cn(
                                'inline-flex shrink-0 items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium',
                                impact.soft,
                            )}
                        >
                            <Check className="h-3 w-3" />
                            {impact.label} Impact
                        </span>
                    </div>
                </div>

                <div className="border-b border-slate-100 px-3 py-2.5">
                    <SectionHeading>Coverage Impact (Staffing Matrix)</SectionHeading>
                    <div className="mt-2 grid grid-cols-2 gap-3">
                        <div className="space-y-1 rounded-lg border border-slate-100 bg-slate-50/60 p-2">
                            <p className="text-[10px] font-medium text-slate-700">{coverage.shift}</p>
                            <p className="text-[10px] text-slate-400">{coverage.department}</p>
                            <MatrixRow label="Required" value={coverage.required ?? '—'} />
                            <MatrixRow label="Scheduled" value={coverage.scheduled ?? '—'} />
                            <MatrixRow label="After Change" value={coverage.after_change ?? '—'} />
                            <MatrixRow
                                label="Coverage"
                                value={Number.isNaN(coveragePct) ? '—' : `${coveragePct}%`}
                                valueClassName={coveragePct >= 100 ? 'text-success' : 'text-amber-600'}
                            />
                        </div>
                        <div className="space-y-1 rounded-lg border border-slate-100 bg-slate-50/60 p-2">
                            <p className="text-[10px] font-medium text-slate-700">
                                Position Forecast{forecast.date ? ` (${forecast.date})` : ''}
                            </p>
                            <p className="text-[10px] text-slate-400">{forecast.position}</p>
                            <MatrixRow label="Required" value={forecast.required ?? '—'} />
                            <MatrixRow label="Forecasted" value={forecast.forecasted ?? '—'} />
                            <MatrixRow
                                label="Variance"
                                value={forecastVariance.text}
                                valueClassName={forecastVariance.className}
                            />
                        </div>
                    </div>
                </div>

                <div className="border-b border-slate-100 px-3 py-2.5">
                    <SectionHeading>Worker Readiness & Conflicts</SectionHeading>
                    <div className="mt-1.5">
                        <ReadinessList readiness={readiness} />
                    </div>
                </div>

                <div className="border-b border-slate-100 px-3 py-2.5">
                    <SectionHeading>Head Office Dependencies</SectionHeading>
                    <p className="mt-1 text-[10px] text-slate-600">{request.head_office || 'None'}</p>
                </div>

                <div className="border-b border-slate-100 px-3 py-2.5">
                    <SectionHeading>Approval Chain</SectionHeading>
                    <ol className="mt-2 flex items-start gap-1">
                        {chain.map((step, index) => {
                            const state = APPROVAL_STATE[step.state] || APPROVAL_STATE.waiting;

                            return (
                                <li key={`${step.name}-${index}`} className="flex min-w-0 flex-1 items-start">
                                    <div className="min-w-0 text-center">
                                        <Avatar
                                            name={step.name}
                                            size="sm"
                                            className="mx-auto h-7 w-7 text-[8px] ring-0"
                                        />
                                        <p className="mt-1 truncate text-[10px] font-semibold text-slate-700">
                                            {step.name}
                                        </p>
                                        <p className="truncate text-[9px] text-slate-400">{step.role}</p>
                                        <p className={cn('text-[9px] font-medium', state.className)}>
                                            {state.label}
                                        </p>
                                    </div>
                                    {index < chain.length - 1 && (
                                        <ArrowRight className="mt-2 h-3.5 w-3.5 shrink-0 text-slate-300" />
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </div>

                <div className="px-3 py-2.5">
                    <div className="flex flex-wrap gap-x-3 gap-y-1 border-b border-slate-100">
                        {TABS.map((item) => {
                            const active = tab === item.key;
                            const label =
                                item.key === 'attachments'
                                    ? `${item.label} (${attachmentCount})`
                                    : item.label;

                            return (
                                <button
                                    key={item.key}
                                    type="button"
                                    onClick={() => setTab(item.key)}
                                    className={cn(
                                        '-mb-px border-b-2 pb-1.5 text-[10px] transition',
                                        active
                                            ? 'border-brand font-semibold text-brand'
                                            : 'border-transparent font-medium text-slate-400 hover:text-slate-600',
                                    )}
                                >
                                    {label}
                                </button>
                            );
                        })}
                    </div>

                    <div className="pt-2.5">
                        {tab === 'timeline' && (
                            <ul className="space-y-2">
                                {timeline.map((entry, index) => (
                                    <li key={`${entry.at}-${index}`} className="flex items-start gap-2">
                                        <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-300" />
                                        <div>
                                            <p className="text-[10px] text-slate-400">{entry.at}</p>
                                            <p className="text-[10px] text-slate-600">{entry.label}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {tab === 'conflicts' && <ReadinessList readiness={readiness} />}

                        {tab === 'context' && (
                            <dl className="space-y-1.5 text-[10px]">
                                <div className="flex justify-between gap-3">
                                    <dt className="text-slate-400">Department</dt>
                                    <dd className="font-medium text-slate-700">{request.department || '—'}</dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-slate-400">Coverage</dt>
                                    <dd className="font-medium text-slate-700">
                                        {coverage.after_change ?? '—'} / {coverage.required ?? '—'} scheduled
                                        {Number.isNaN(coveragePct) ? '' : ` (${coveragePct}%)`}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-slate-400">Position forecast</dt>
                                    <dd className="font-medium text-slate-700">
                                        {forecast.position || '—'}
                                        {forecast.date ? ` · ${forecast.date}` : ''}
                                    </dd>
                                </div>
                            </dl>
                        )}

                        {tab === 'attachments' && (
                            attachmentCount === 0 ? (
                                <p className="text-[10px] text-slate-400">No attachments.</p>
                            ) : (
                                <ul className="space-y-1.5">
                                    {Array.from({ length: attachmentCount }, (_, index) => (
                                        <li
                                            key={index}
                                            className="flex items-start gap-1.5 rounded-md border border-slate-100 px-2 py-1.5"
                                        >
                                            <Paperclip className="mt-px h-3.5 w-3.5 shrink-0 text-slate-400" />
                                            <div>
                                                <p className="text-[10px] font-medium text-slate-600">
                                                    Attachment {index + 1}
                                                </p>
                                                <p className="text-[9px] text-slate-400">No preview available</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )
                        )}
                    </div>
                </div>
            </div>

            <div className="mt-auto flex flex-wrap gap-1.5 border-t border-slate-100 px-3 py-2">
                <button
                    type="button"
                    disabled={!canApprove}
                    title={disabledTitle}
                    onClick={() => onAction('approve', request.id)}
                    className="inline-flex items-center justify-center rounded-lg bg-success px-2.5 py-1.5 text-[10px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Approve
                </button>
                <button
                    type="button"
                    disabled={!canApprove}
                    title={disabledTitle}
                    onClick={() => onAction('reject', request.id)}
                    className="inline-flex items-center justify-center rounded-lg bg-danger px-2.5 py-1.5 text-[10px] font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Reject
                </button>
                <button
                    type="button"
                    disabled={!canApprove}
                    title={disabledTitle}
                    onClick={() => onAction('info', request.id)}
                    className="inline-flex items-center justify-center rounded-lg border border-brand px-2.5 py-1.5 text-[10px] font-semibold text-brand transition hover:bg-brand-soft disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Request More Info
                </button>
                <button
                    type="button"
                    disabled={!canApprove}
                    title={disabledTitle}
                    onClick={() => onAction('escalate', request.id)}
                    className="btn-secondary min-h-0 gap-1 px-2.5 py-1.5 text-[10px] disabled:cursor-not-allowed"
                >
                    <ArrowUp className="h-3 w-3" />
                    Escalate
                </button>
            </div>
        </aside>
    );
}
