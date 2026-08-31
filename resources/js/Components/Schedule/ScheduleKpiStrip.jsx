import { cn } from '@/utils/helpers';
import { KPI_ICONS, KPI_TONE } from './scheduleDesign';

/**
 * The headline cards above the schedule views. The server names the icon and
 * tone per card, so the same strip renders both the workforce KPIs and the
 * change-request KPIs.
 */
function hintClass(hint = '', isLink = false) {
    if (isLink) {
        return 'text-danger underline decoration-danger/40 underline-offset-2';
    }

    if (hint.startsWith('+')) {
        return 'text-success';
    }

    if (hint.startsWith('-')) {
        return 'text-danger';
    }

    return 'text-slate-500';
}

export default function ScheduleKpiStrip({ items = [], onHintClick }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="grid grid-cols-2 gap-2.5 md:grid-cols-3 xl:grid-cols-5">
            {items.map((item) => {
                const Icon = KPI_ICONS[item.icon] || KPI_ICONS.Users;
                const isLink = Boolean(item.hint_is_link);

                return (
                    <div key={item.key} className="card flex items-start justify-between gap-2 px-3 py-2.5">
                        <div className="min-w-0">
                            <p className="truncate text-[10px] font-medium text-slate-500">{item.label}</p>
                            <p className="mt-1 text-xl font-semibold leading-none text-slate-900">{item.value}</p>
                            {item.hint &&
                                (isLink ? (
                                    <button
                                        type="button"
                                        onClick={() => onHintClick?.(item)}
                                        className={cn('mt-1.5 text-[10px]', hintClass(item.hint, true))}
                                    >
                                        {item.hint}
                                    </button>
                                ) : (
                                    <p className={cn('mt-1.5 truncate text-[10px]', hintClass(item.hint))}>
                                        {item.hint}
                                    </p>
                                ))}
                        </div>

                        <span
                            className={cn(
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-lg',
                                KPI_TONE[item.tone] || KPI_TONE.brand,
                            )}
                        >
                            <Icon className="h-4 w-4" strokeWidth={1.8} />
                        </span>
                    </div>
                );
            })}
        </div>
    );
}
