import { STATUS_COLORS } from '@/utils/constants';
import { statusLabel } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

const toneClasses = {
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
    journey: 'bg-journey-soft text-journey',
    slate: 'bg-slate-100 text-slate-600',
    brand: 'bg-brand-soft text-brand',
};

export default function Badge({ status, tone, children, className = '' }) {
    const resolvedTone = tone || STATUS_COLORS[status] || 'slate';

    return (
        <span className={cn('badge', toneClasses[resolvedTone] || toneClasses.slate, className)}>
            {children ?? statusLabel(status)}
        </span>
    );
}
