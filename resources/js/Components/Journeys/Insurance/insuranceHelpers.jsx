import { cn } from '@/utils/helpers';

const STATUS_CLASSES = {
    confirmed: 'bg-success-soft text-success',
    unverified: 'bg-warning-soft text-warning',
    flagged: 'bg-danger-soft text-danger',
};

/** Whether the policy itself is in date, independent of who has checked it. */
export function coverState(vehicle) {
    if (!vehicle.policy_end_date) {
        return { key: 'missing', label: 'No policy', className: 'bg-slate-100 text-slate-600' };
    }

    if (!vehicle.insurance_valid) {
        return { key: 'expired', label: 'Expired', className: 'bg-danger-soft text-danger' };
    }

    if (vehicle.insurance_expiring_soon) {
        return { key: 'expiring', label: 'Expiring soon', className: 'bg-warning-soft text-warning' };
    }

    return { key: 'valid', label: 'In cover', className: 'bg-success-soft text-success' };
}

export function CoverBadge({ vehicle, className }) {
    const state = coverState(vehicle);

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                state.className,
                className,
            )}
        >
            {state.label}
        </span>
    );
}

export function InsuranceStatusBadge({ vehicle, className }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                STATUS_CLASSES[vehicle.insurance_status] || 'bg-slate-100 text-slate-600',
                className,
            )}
        >
            {vehicle.insurance_status_label}
        </span>
    );
}
