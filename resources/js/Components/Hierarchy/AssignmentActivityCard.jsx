import Avatar from '@/Components/Shared/Avatar';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';

export default function AssignmentActivityCard({ activity = [] }) {
    return (
        <Card className="h-full" title="Recent Assignment Activity">
            {activity.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No activity yet"
                    description="Manager and delegation changes appear here."
                />
            ) : (
                <div className="-mx-4 table-wrap sm:-mx-5">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {activity.map((entry) => (
                                <tr key={entry.id}>
                                    <td className="text-xs text-slate-500">{entry.occurred_at}</td>
                                    <td>
                                        <span className="flex items-center gap-2">
                                            <Avatar name={entry.actor} size="sm" />
                                            <span className="truncate font-medium text-slate-900">
                                                {entry.actor}
                                            </span>
                                        </span>
                                    </td>
                                    <td>{entry.action}</td>
                                    <td className="max-w-[220px] truncate text-xs text-slate-500">
                                        {entry.details}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Card>
    );
}
