import { CalendarClock, MoreVertical, Plus } from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import IconButton from '@/Components/Shared/IconButton';
import PanelTitle from './PanelTitle';

export default function ScheduledReportsPanel({ schedules = [], onNewSchedule }) {
    return (
        <Card
            title={<PanelTitle icon={CalendarClock}>Scheduled Reports</PanelTitle>}
            actions={
                <button
                    type="button"
                    onClick={onNewSchedule}
                    className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    <Plus className="h-3 w-3" />
                    New Schedule
                </button>
            }
            className="h-full"
        >
            <ul className="divide-y divide-slate-100">
                {schedules.length === 0 && (
                    <li className="py-4 text-sm text-slate-500">
                        Recurring email schedules are not configured yet. Use Export or the report library for a live CSV.
                    </li>
                )}
                {schedules.map((schedule) => (
                    <li key={schedule.id} className="flex items-start gap-2 py-2.5 first:pt-0">
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-slate-900">
                                {schedule.name}
                            </p>
                            <p className="truncate text-xs text-slate-500">{schedule.cadence}</p>
                            <p className="truncate text-xs text-slate-400">{schedule.recipient}</p>
                        </div>
                        <Badge status={schedule.status} />
                        <IconButton label={`Options for ${schedule.name}`} className="h-6 w-6">
                            <MoreVertical className="h-4 w-4" />
                        </IconButton>
                    </li>
                ))}
            </ul>
        </Card>
    );
}
