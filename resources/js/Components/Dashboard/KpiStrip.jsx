import { cn } from '@/utils/helpers';
import GradeBadge from './GradeBadge';

const ICON_TONES = {
    brand: 'text-brand',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
    journey: 'text-journey',
    slate: 'text-slate-500',
};

/**
 * The command-bar of KPI tiles. Cells sit on a 1px slate background so the
 * hairline dividers survive wrapping at narrow widths.
 */
export default function KpiStrip({ items = [] }) {
    return (
        <div className="card overflow-hidden">
            <div className="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 xl:grid-cols-8">
                {items.map((item) => {
                    const Icon = item.icon;

                    return (
                        <div
                            key={item.label}
                            className="flex flex-col items-center gap-1.5 bg-white px-2 py-2.5"
                        >
                            <p className="text-center text-[10px] font-medium leading-tight text-slate-500">
                                {item.label}
                            </p>
                            <div className="flex items-center gap-2">
                                {item.grade ? (
                                    <GradeBadge grade={item.grade} size="md" />
                                ) : (
                                    Icon && (
                                        <Icon
                                            className={cn(
                                                'h-[18px] w-[18px] shrink-0',
                                                ICON_TONES[item.tone] || ICON_TONES.brand,
                                            )}
                                            strokeWidth={2.2}
                                        />
                                    )
                                )}
                                <div className="min-w-0">
                                    <p className="text-[15px] font-bold leading-tight text-slate-900">
                                        {item.value}
                                    </p>
                                    {item.hint && (
                                        <p className="truncate text-[9px] leading-tight text-slate-500">
                                            {item.hint}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
