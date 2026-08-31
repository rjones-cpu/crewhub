import { cn } from '@/utils/helpers';

export const SCHEDULE_VIEWS = [
    { key: 'board', label: 'Board View' },
    { key: 'list', label: 'List View' },
    { key: 'calendar', label: 'Calendar View' },
    { key: 'requests', label: 'Change Requests' },
];

export default function ScheduleViewTabs({ active = 'list', counts = {}, onChange = () => {} }) {
    return (
        <nav className="flex gap-5 border-b border-slate-200">
            {SCHEDULE_VIEWS.map((view) => {
                const isActive = view.key === active;

                return (
                    <button
                        key={view.key}
                        type="button"
                        onClick={() => onChange(view.key)}
                        className={cn(
                            '-mb-px flex items-center gap-1.5 whitespace-nowrap border-b-2 pb-2 text-xs transition',
                            isActive
                                ? 'border-brand font-semibold text-brand'
                                : 'border-transparent font-medium text-slate-500 hover:text-slate-700',
                        )}
                    >
                        {view.label}
                        {counts[view.key] > 0 && (
                            <span className="rounded-full bg-slate-100 px-1.5 text-[10px] font-semibold text-slate-600">
                                {counts[view.key]}
                            </span>
                        )}
                    </button>
                );
            })}
        </nav>
    );
}
