import { cn } from '@/utils/helpers';

export const RISK_PILL_CLASSES = {
    low: 'bg-success-soft text-success',
    medium: 'bg-warning-soft text-warning',
    high: 'bg-danger-soft text-danger',
};

export const RISK_BAR_CLASSES = {
    low: 'bg-success',
    medium: 'bg-amber-400',
    high: 'bg-danger',
};

export const RISK_TEXT_CLASSES = {
    low: 'text-success',
    medium: 'text-warning',
    high: 'text-danger',
};

export function riskLevelFor(score) {
    if (score >= 70) {
        return 'high';
    }

    return score >= 40 ? 'medium' : 'low';
}

export function RiskPill({ level, label, className }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                RISK_PILL_CLASSES[level] || 'bg-slate-100 text-slate-600',
                className,
            )}
        >
            {label || level || '—'}
        </span>
    );
}

/** Score readout with a proportional bar, used in both the table and the panel. */
export function ScoreBar({ score = 0, level, showValue = true, className }) {
    const tone = level || riskLevelFor(score);

    return (
        <div className={cn('min-w-[72px]', className)}>
            {showValue && (
                <p className="text-[11px] font-medium text-slate-700">{score}/100</p>
            )}
            <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                <div
                    className={cn('h-full rounded-full', RISK_BAR_CLASSES[tone] || 'bg-slate-300')}
                    style={{ width: `${Math.min(100, Math.max(0, score))}%` }}
                />
            </div>
        </div>
    );
}
