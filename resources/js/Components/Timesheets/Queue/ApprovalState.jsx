import { cn } from '@/utils/helpers';

// Deliberately local to the approval queue: `returned` reads as red here, which
// differs from the shared STATUS_COLORS mapping used elsewhere.
export const APPROVAL_STATES = {
    approved: { label: 'Approved', badge: 'bg-success-soft text-success', dot: 'bg-success' },
    confirmed: { label: 'Confirmed', badge: 'bg-brand-soft text-brand', dot: 'bg-brand' },
    pending: { label: 'Pending', badge: 'bg-amber-50 text-amber-600', dot: 'bg-amber-400' },
    returned: { label: 'Returned', badge: 'bg-danger-soft text-danger', dot: 'bg-danger' },
    not_required: { label: 'Not Required', badge: 'bg-slate-100 text-slate-500', dot: 'bg-slate-300' },
    overdue: { label: 'Overdue', badge: 'bg-danger-soft text-danger', dot: 'bg-danger' },
};

export function ApprovalStateBadge({ state, className = '' }) {
    const config = APPROVAL_STATES[state] || APPROVAL_STATES.not_required;

    return (
        <span className={cn('badge whitespace-nowrap !px-1.5 !py-0.5 !text-[8px]', config.badge, className)}>
            {config.label}
        </span>
    );
}

export default function ApprovalStateCell({ state, at }) {
    return (
        <div className="space-y-px">
            <ApprovalStateBadge state={state} />
            {at && <p className="text-[7px] leading-tight text-slate-400">{at}</p>}
        </div>
    );
}
