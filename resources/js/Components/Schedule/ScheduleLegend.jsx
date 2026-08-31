import { cn } from '@/utils/helpers';
import { SHIFT_STATUS } from './scheduleDesign';

/** Dot-and-label legend for the day-cell statuses in the List View. */
export default function ScheduleLegend({ items = [], className = '' }) {
    return (
        <div className={cn('flex flex-wrap items-center gap-3', className)}>
            {items.map((key) => {
                const status = SHIFT_STATUS[key];

                if (!status) {
                    return null;
                }

                return (
                    <span key={key} className="flex items-center gap-1.5 text-[10px] text-slate-500">
                        <span className={cn('h-2 w-2 rounded-full', status.dot)} />
                        {status.label}
                    </span>
                );
            })}
        </div>
    );
}
