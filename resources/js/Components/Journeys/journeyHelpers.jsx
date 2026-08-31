import { JOURNEY_STATUS_OPTIONS } from '@/utils/constants';
import { cn } from '@/utils/helpers';

const STATUS_CLASSES = {
    pending: 'bg-warning-soft text-warning',
    approved: 'bg-sky-50 text-sky-700',
    in_transit: 'bg-brand text-white',
    completed: 'bg-success-soft text-success',
    cancelled: 'bg-slate-100 text-slate-600',
};

const RISK_DOT = {
    high: 'bg-danger',
    medium: 'bg-amber-400',
    low: 'bg-success',
};

const RISK_BAR = {
    high: 'bg-danger',
    medium: 'bg-amber-400',
    low: 'bg-success',
};

export function journeyStatusValue(status) {
    return status?.value ?? status ?? '';
}

export function journeyStatusLabel(status, fallback) {
    const value = journeyStatusValue(status);

    return JOURNEY_STATUS_OPTIONS.find((option) => option.value === value)?.label
        || fallback
        || '—';
}

export function JourneyStatusBadge({ status, label }) {
    const value = journeyStatusValue(status);

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                STATUS_CLASSES[value] || 'bg-slate-100 text-slate-600',
            )}
        >
            {label || journeyStatusLabel(status)}
        </span>
    );
}

export function RiskMeter({ level, segments = 0, label }) {
    const value = level?.value ?? level ?? '';
    const filled = segments || (value === 'high' ? 3 : value === 'medium' ? 2 : value === 'low' ? 1 : 0);

    return (
        <span className="inline-flex items-center gap-1.5">
            <span className={cn('h-1.5 w-1.5 rounded-full', RISK_DOT[value] || 'bg-slate-300')} />
            <span className="text-xs font-medium text-slate-700">{label || value || '—'}</span>
            <span className="inline-flex gap-0.5" aria-hidden="true">
                {[1, 2, 3].map((index) => (
                    <span
                        key={index}
                        className={cn(
                            'h-1.5 w-3 rounded-sm',
                            index <= filled ? RISK_BAR[value] || 'bg-slate-300' : 'bg-slate-200',
                        )}
                    />
                ))}
            </span>
        </span>
    );
}
