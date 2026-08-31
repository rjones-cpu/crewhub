import { CheckCircle2, Clock, FileWarning, RotateCcw } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { cn } from '@/utils/helpers';

const TONE_ICON = {
    success: CheckCircle2,
    warning: Clock,
    danger: FileWarning,
    brand: RotateCcw,
    slate: Clock,
};

const TONE_CLASS = {
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
    brand: 'bg-brand-soft text-brand',
    slate: 'bg-slate-100 text-slate-500',
};

function relativeTime(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export default function RecentTimesheetActivity({ activities = [] }) {
    return (
        <Card title="Recent Timesheet Activity" className="h-full">
            {activities.length === 0 ? (
                <EmptyState title="No recent activity" description="Submissions and approvals will appear here." />
            ) : (
                <ul className="space-y-3">
                    {activities.map((item) => {
                        const Icon = TONE_ICON[item.tone] || Clock;

                        return (
                            <li key={item.id} className="flex items-start gap-3">
                                <span
                                    className={cn(
                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                        TONE_CLASS[item.tone] || TONE_CLASS.slate,
                                    )}
                                >
                                    <Icon className="h-4 w-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm text-slate-800">{item.description}</p>
                                    <p className="mt-0.5 text-xs text-slate-400">{relativeTime(item.at)}</p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </Card>
    );
}
