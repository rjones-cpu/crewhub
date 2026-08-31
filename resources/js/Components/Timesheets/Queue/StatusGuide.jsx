import { APPROVAL_STATES } from './ApprovalState';

const GUIDE = [
    ['approved', 'Step completed successfully'],
    ['pending', 'Waiting for action from next approver'],
    ['returned', 'Returned for correction or changes'],
    ['confirmed', 'Step verified with AI verification completed'],
    ['not_required', 'Step not required for this timesheet'],
    ['overdue', 'Action required past due date'],
];

export default function StatusGuide() {
    return (
        <section>
            <h3 className="mb-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-700">
                Status Guide
            </h3>
            <dl className="space-y-1">
                {GUIDE.map(([state, description]) => (
                    <div key={state} className="flex items-start gap-1.5 text-[7px]">
                        <span
                            className={`mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full ${APPROVAL_STATES[state].dot}`}
                        />
                        <dt className="w-12 shrink-0 font-semibold text-slate-700">
                            {APPROVAL_STATES[state].label}
                        </dt>
                        <dd className="min-w-0 text-slate-500">{description}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
