import { CalendarClock, UserCheck, UserX, Users } from 'lucide-react';
import { formatNumber } from '@/utils/formatters';

export default function WorkerStats({ stats = {} }) {
    const cards = [
        { label: 'Total Workers', value: stats.total, hint: 'Across all projects', icon: Users, tone: 'text-indigo-600 bg-indigo-50' },
        { label: 'Active', value: stats.active, hint: 'Currently active', icon: UserCheck, tone: 'text-emerald-600 bg-emerald-50' },
        { label: 'Inactive', value: stats.inactive, hint: 'Not active', icon: UserX, tone: 'text-orange-600 bg-orange-50' },
        { label: 'On Leave', value: stats.on_leave, hint: 'On leave', icon: CalendarClock, tone: 'text-violet-600 bg-violet-50' },
    ];

    return (
        <div className="grid overflow-hidden rounded-md border border-slate-200 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map(({ label, value, hint, icon: Icon, tone }, index) => (
                <div
                    key={label}
                    className={`flex min-h-[62px] items-center gap-2.5 bg-white px-3 py-2 ${
                        index ? 'border-t border-slate-200 sm:border-l xl:border-t-0' : ''
                    }`}
                >
                    <div className={`grid h-7 w-7 shrink-0 place-items-center rounded-full ${tone}`}>
                        <Icon className="h-3.5 w-3.5" />
                    </div>
                    <div>
                        <p className="text-[8px] font-medium text-slate-500">{label}</p>
                        <p className="text-[17px] font-bold leading-5 text-slate-900">{formatNumber(value || 0)}</p>
                        <p className="text-[8px] text-slate-400">{hint}</p>
                    </div>
                </div>
            ))}
        </div>
    );
}
