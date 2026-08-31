import { BadgeCheck, FileCheck2, Plane, ShieldCheck } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';

const ACTIVITY_ICONS = [
    { match: 'medical', icon: FileCheck2, tone: 'bg-success-soft text-success' },
    { match: 'cert', icon: BadgeCheck, tone: 'bg-amber-50 text-amber-500' },
    { match: 'journey', icon: Plane, tone: 'bg-journey-soft text-journey' },
];

export default function RecentReadinessActivity({ activities = [] }) {
    return (
        <Card padding={false} className="rounded-lg p-3">
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-[10px] font-bold text-slate-800">Recent Readiness Activity</h3>
                <span className="text-[7px] font-semibold text-brand">View all</span>
            </div>
            {activities.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No recent activity"
                    description="Readiness updates will appear here."
                />
            ) : (
                <ul className="divide-y divide-slate-100">
                    {activities.map((item, index) => {
                        const haystack = `${item.type || ''} ${item.description || ''}`.toLowerCase();
                        const config = ACTIVITY_ICONS.find((entry) => haystack.includes(entry.match))
                            || { icon: ShieldCheck, tone: 'bg-brand-soft text-brand' };
                        const Icon = config.icon;

                        return (
                        <li key={item.id || index} className="flex items-center justify-between gap-2 py-1.5">
                            <div className="flex min-w-0 items-center gap-2">
                                <span className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${config.tone}`}>
                                    <Icon className="h-3 w-3" />
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate text-[7px] font-semibold text-slate-700">
                                        {item.description || item.title || item.message}
                                    </p>
                                    <p className="truncate text-[7px] text-slate-400">
                                        {item.worker || item.type || 'Update'}
                                    </p>
                                </div>
                            </div>
                            <span className="shrink-0 text-[7px] text-slate-400">
                                {item.created_at || item.time || ''}
                            </span>
                        </li>
                        );
                    })}
                </ul>
            )}
        </Card>
    );
}
