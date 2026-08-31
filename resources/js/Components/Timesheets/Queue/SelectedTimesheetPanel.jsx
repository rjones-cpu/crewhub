import {
    Check,
    CheckCircle2,
    Circle,
    MessageSquarePlus,
    Paperclip,
    PencilLine,
    Undo2,
} from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import EmptyState from '@/Components/Shared/EmptyState';
import { cn } from '@/utils/helpers';
import { ApprovalStateBadge } from './ApprovalState';
import StatusGuide from './StatusGuide';

const COMPLETED_STATES = ['approved', 'confirmed'];
const STAGE_TONES = {
    draft: 'bg-success-soft text-success',
    submitted: 'bg-success-soft text-success',
    manager_approved: 'bg-violet-50 text-violet-600',
    fully_approved: 'bg-success-soft text-success',
    returned: 'bg-danger-soft text-danger',
    rejected: 'bg-danger-soft text-danger',
};

function DetailRow({ label, value }) {
    return (
        <div className="flex items-baseline gap-1.5">
            <dt className="w-[58px] shrink-0 text-[8px] text-slate-500">{label}</dt>
            <dd className="min-w-0 text-[8px] font-semibold text-slate-900">{value ?? '—'}</dd>
        </div>
    );
}

export default function SelectedTimesheetPanel({ timesheet, onApprove, onReturn, onRequestChanges }) {
    if (!timesheet) {
        return (
            <div className="card">
                <EmptyState
                    title="No timesheet selected"
                    description="Pick a row in the approval queue to review its approval record."
                />
            </div>
        );
    }

    const { worker, approval_record: record = [], notes = [], attachments = [], can = {} } = timesheet;
    const canApprove = can.approve_manager || can.approve_client;

    return (
        <div className="card space-y-3 rounded-lg p-3">
            <div className="flex items-start justify-between gap-2">
                <h2 className="text-[9px] font-bold uppercase tracking-wider text-slate-700">
                    Selected Timesheet
                </h2>
                <span
                    className={cn(
                        'badge !px-1.5 !py-0.5 !text-[7px]',
                        STAGE_TONES[timesheet.status] || 'bg-slate-100 text-slate-600',
                    )}
                >
                    {timesheet.stage}
                </span>
            </div>

            <div className="flex items-center gap-2">
                <Avatar name={worker.name} src={worker.avatar} size="sm" className="h-7 w-7 text-[9px]" />
                <div className="min-w-0">
                    <p className="truncate text-[10px] font-semibold text-slate-900">{worker.name}</p>
                    <p className="truncate text-[8px] text-slate-500">
                        {worker.employee_id} · {worker.position}
                    </p>
                    <p className="truncate text-[8px] text-slate-400">{worker.company}</p>
                </div>
            </div>

            <dl className="space-y-1">
                <DetailRow label="Week" value={timesheet.week} />
                <DetailRow label="Total Hours" value={timesheet.total_hours} />
                <DetailRow label="Timesheet ID" value={timesheet.reference} />
                <DetailRow label="Submitted" value={timesheet.submitted_at} />
            </dl>

            <section>
                <h3 className="mb-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-700">
                    Approval Record
                </h3>
                <ul className="space-y-1.5">
                    {record.map((step) => {
                        const complete = COMPLETED_STATES.includes(step.state);
                        const StepIcon = complete ? CheckCircle2 : Circle;

                        return (
                            <li key={step.key} className="flex items-start gap-1.5">
                                <StepIcon
                                    className={cn(
                                        'mt-px h-3 w-3 shrink-0',
                                        step.state === 'approved' && 'text-success',
                                        step.state === 'confirmed' && 'text-brand',
                                        step.state === 'returned' && 'text-danger',
                                        !complete && step.state !== 'returned' && 'text-slate-300',
                                    )}
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="text-[8px] font-semibold text-slate-900">
                                        {step.title}
                                    </p>
                                    <p className="truncate text-[7px] text-slate-500">
                                        {step.actor}
                                    </p>
                                    {step.at && (
                                        <p className="text-[7px] text-slate-400">{step.at}</p>
                                    )}
                                </div>
                                <ApprovalStateBadge state={step.state} />
                            </li>
                        );
                    })}
                </ul>
            </section>

            <section>
                <h3 className="mb-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-700">
                    Approval Actions
                </h3>
                <div className="flex flex-wrap gap-1">
                    <button
                        type="button"
                        disabled={!canApprove}
                        onClick={onApprove}
                        className="inline-flex items-center gap-1 rounded bg-success px-1.5 py-1 text-[7px] font-semibold text-white transition hover:bg-green-700 disabled:opacity-40"
                    >
                        <Check className="h-2.5 w-2.5" />
                        Approve
                    </button>
                    <button
                        type="button"
                        disabled={!can.return}
                        onClick={onReturn}
                        className="inline-flex items-center gap-1 rounded border border-warning/40 px-1.5 py-1 text-[7px] font-semibold text-warning transition hover:bg-warning-soft disabled:opacity-40"
                    >
                        <Undo2 className="h-2.5 w-2.5" />
                        Return for Correction
                    </button>
                    <button
                        type="button"
                        disabled={!can.return}
                        onClick={onRequestChanges}
                        className="inline-flex items-center gap-1 rounded border border-slate-200 px-1.5 py-1 text-[7px] font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                    >
                        <PencilLine className="h-2.5 w-2.5" />
                        Request Changes
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Standalone comments are not available yet"
                        className="inline-flex items-center gap-1 rounded border border-slate-200 px-1.5 py-1 text-[7px] font-semibold text-slate-600 transition disabled:opacity-40"
                    >
                        <MessageSquarePlus className="h-2.5 w-2.5" />
                        Add Comment
                    </button>
                </div>
            </section>

            <div className="grid gap-3 sm:grid-cols-2">
                <section>
                    <h3 className="mb-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-700">
                        Notes &amp; Comments
                    </h3>
                    {notes.length === 0 ? (
                        <p className="text-[7px] text-slate-400">No comments on this timesheet.</p>
                    ) : (
                        <ul className="space-y-2">
                            {notes.slice(0, 1).map((note) => (
                                <li key={note.id} className="flex items-start gap-2">
                                    <Avatar
                                        name={note.author}
                                        size="sm"
                                        className="h-5 w-5 text-[7px] ring-0"
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate text-[7px] font-semibold text-slate-900">
                                            {note.author}
                                        </p>
                                        <p className="text-[7px] text-slate-400">{note.at}</p>
                                        <p className="mt-0.5 text-[7px] leading-snug text-slate-600">
                                            {note.body}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    {notes.length > 1 && (
                        <p className="mt-1 text-[7px] font-semibold text-brand">
                            View all comments ({notes.length})
                        </p>
                    )}
                </section>

                <section>
                    <div className="mb-2 flex items-center justify-between gap-2">
                        <h3 className="text-[9px] font-bold uppercase tracking-wider text-slate-700">
                            Attachments
                        </h3>
                        <span className="flex items-center gap-1 text-[7px] text-slate-400">
                            <Paperclip className="h-2.5 w-2.5" />
                            {attachments.length}
                        </span>
                    </div>
                    {attachments.length === 0 ? (
                        <p className="text-[7px] text-slate-400">No attachments.</p>
                    ) : (
                        <ul className="space-y-1.5">
                            {attachments.map((file) => (
                                <li key={file.name} className="text-[7px]">
                                    <p className="truncate font-medium text-brand">{file.name}</p>
                                    <p className="text-slate-400">{file.size}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <StatusGuide />
        </div>
    );
}
