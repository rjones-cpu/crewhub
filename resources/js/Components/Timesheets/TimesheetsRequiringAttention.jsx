import { Link } from '@inertiajs/react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate } from '@/utils/formatters';

export default function TimesheetsRequiringAttention({ items = [] }) {
    return (
        <Card title="Timesheets Requiring Attention" className="h-full">
            {items.length === 0 ? (
                <EmptyState title="All clear" description="No timesheets need attention right now." />
            ) : (
                <div className="-mx-4 table-wrap sm:-mx-5">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Company</th>
                                <th>Project</th>
                                <th>Issue</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {items.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <div className="flex items-center gap-2.5">
                                            <Avatar name={row.worker_name} size="sm" />
                                            <div>
                                                <p className="font-medium text-slate-900">{row.worker_name}</p>
                                                <p className="text-xs text-slate-500">{row.worker_id}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{row.company}</td>
                                    <td>{row.project}</td>
                                    <td>{row.issue}</td>
                                    <td>{formatDate(row.due_date)}</td>
                                    <td>
                                        <Badge status={row.status} />
                                    </td>
                                    <td>
                                        <Link
                                            href={route('timesheets.show', row.id)}
                                            className="text-sm font-medium text-brand hover:underline"
                                        >
                                            {row.status === 'pending' || row.status === 'overdue'
                                                ? 'Review'
                                                : 'View'}
                                        </Link>
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
