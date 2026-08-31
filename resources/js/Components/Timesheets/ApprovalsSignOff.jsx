import Card from '@/Components/Shared/Card';
import { formatDate } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

export default function ApprovalsSignOff({
    timesheet,
    editable,
    canApproveManager,
    canApproveClient,
    onChange,
}) {
    return (
        <Card title="Approvals & Sign-Off">
            <div className="grid gap-6 lg:grid-cols-3">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Worker Signature</p>
                    {editable ? (
                        <input
                            className="input-field mt-2 font-serif italic"
                            placeholder="Type full name to sign"
                            value={timesheet.worker_signature || ''}
                            onChange={(e) =>
                                onChange?.({
                                    worker_signature: e.target.value,
                                    compliance: {
                                        ...timesheet.compliance,
                                        signature: Boolean(e.target.value.trim()),
                                    },
                                })
                            }
                        />
                    ) : (
                        <p className="mt-2 font-serif text-xl italic text-slate-800">
                            {timesheet.worker_signature || '—'}
                        </p>
                    )}
                    <p className="mt-2 text-xs text-slate-500">
                        Submit date:{' '}
                        {timesheet.submitted_at
                            ? formatDate(timesheet.submitted_at)
                            : 'Not submitted'}
                    </p>
                </div>

                <div>
                    <label className="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Manager Comment
                    </label>
                    <textarea
                        className="input-field mt-2 min-h-[90px]"
                        value={timesheet.manager_comment || ''}
                        disabled={!canApproveManager && !editable}
                        onChange={(e) => onChange?.({ manager_comment: e.target.value })}
                        placeholder="Manager notes…"
                    />
                </div>

                <div>
                    <label className="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Client Comment
                    </label>
                    <textarea
                        className="input-field mt-2 min-h-[90px]"
                        value={timesheet.client_comment || ''}
                        disabled={!canApproveClient}
                        onChange={(e) => onChange?.({ client_comment: e.target.value })}
                        placeholder={
                            timesheet.client_approval_required
                                ? 'Client notes…'
                                : 'Client approval not required'
                        }
                    />
                </div>
            </div>

            <div className="mt-6 border-t border-slate-100 pt-4">
                <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">Status History</p>
                <ol className="space-y-3">
                    {(timesheet.status_history || []).map((step, index) => (
                        <li key={`${step.status}-${index}`} className="flex gap-3">
                            <span
                                className={cn(
                                    'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                    step.current ? 'bg-brand' : 'bg-slate-300',
                                )}
                            />
                            <div>
                                <p className="text-sm font-medium text-slate-900">
                                    {step.label || step.status}
                                    {step.current ? (
                                        <span className="ml-2 text-xs font-normal text-brand">(Current)</span>
                                    ) : null}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {step.at ? formatDate(step.at) : ''}
                                    {step.by ? ` · ${step.by}` : ''}
                                    {step.note ? ` — ${step.note}` : ''}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </Card>
    );
}
