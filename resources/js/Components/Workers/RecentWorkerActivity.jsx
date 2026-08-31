import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';

export default function RecentWorkerActivity({ activities = [] }) {
    return (
        <Card title="Recent Worker Activity">
            {activities.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No recent activity"
                    description="Worker updates will appear here."
                />
            ) : (
                <ul className="divide-y divide-slate-100">
                    {activities.map((item, index) => (
                        <li key={item.id || index} className="flex items-start justify-between gap-4 py-3 text-sm">
                            <div className="min-w-0">
                                <p className="font-medium text-slate-900">
                                    {item.description || item.title || item.message}
                                </p>
                                <p className="truncate text-slate-500">
                                    {item.worker_name || item.worker || item.type || 'Activity'}
                                </p>
                            </div>
                            <span className="shrink-0 text-xs text-slate-400">
                                {item.created_at || item.time || ''}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
