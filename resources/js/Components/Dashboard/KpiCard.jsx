import { ArrowDown, ArrowUp } from 'lucide-react';
import { cn } from '@/utils/helpers';

const ICON_TONES = {
    brand: 'bg-brand-soft text-brand',
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
    journey: 'bg-journey-soft text-journey',
    slate: 'bg-slate-100 text-slate-500',
};

export default function KpiCard({
    label,
    value,
    hint,
    delta,
    trend,
    icon: Icon,
    tone = 'brand',
    className = '',
    compact = false,
}) {
    const TrendIcon = trend?.direction === 'down' ? ArrowDown : ArrowUp;

    return (
        <div className={cn('card', compact ? 'p-3' : 'card-padding', className)}>
            <div className={cn('flex items-start justify-between', compact ? 'gap-2' : 'gap-3')}>
                <div className="min-w-0">
                    <p className={cn('kpi-label', compact && 'text-[10px] leading-tight')}>{label}</p>
                    <p className={cn('kpi-value', compact && 'mt-1 text-xl leading-none')}>{value}</p>
                    {trend && (
                        <p
                            className={cn(
                                'mt-1 flex items-center gap-1 text-slate-500',
                                compact ? 'text-[10px]' : 'text-xs',
                            )}
                        >
                            <TrendIcon
                                className={cn(
                                    'h-3 w-3 shrink-0',
                                    trend.tone === 'danger' ? 'text-danger' : 'text-success',
                                )}
                            />
                            <span
                                className={cn(
                                    'font-medium',
                                    trend.tone === 'danger' ? 'text-danger' : 'text-success',
                                )}
                            >
                                {trend.value}
                            </span>
                            {trend.label}
                        </p>
                    )}
                    {!trend && (hint || delta !== undefined) && (
                        <p className={cn('mt-1 text-slate-500', compact ? 'text-[10px]' : 'text-xs')}>
                            {delta !== undefined && delta !== null && (
                                <span
                                    className={cn(
                                        'mr-1 font-medium',
                                        Number(delta) >= 0 ? 'text-success' : 'text-danger',
                                    )}
                                >
                                    {Number(delta) > 0 ? '+' : ''}
                                    {delta}
                                </span>
                            )}
                            {hint}
                        </p>
                    )}
                </div>
                {Icon && (
                    <div
                        className={cn(
                            'flex shrink-0 items-center justify-center rounded-lg',
                            compact ? 'h-8 w-8' : 'h-10 w-10',
                            ICON_TONES[tone] || ICON_TONES.brand,
                        )}
                    >
                        <Icon className={compact ? 'h-4 w-4' : 'h-5 w-5'} />
                    </div>
                )}
            </div>
        </div>
    );
}
