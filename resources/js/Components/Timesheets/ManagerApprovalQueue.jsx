import { Link } from '@inertiajs/react';
import Avatar from '@/Components/Shared/Avatar';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate } from '@/utils/formatters';

export default function ManagerApprovalQueue({ queue = [] }) {
    return (
        <Card title="Manager Approval Queue" className="h-full">
            {queue.length === 0 ? (
                <EmptyState title="Queue clear" description="No managers have pending timesheets." />
            ) : (
                <div className="-mx-4 table-wrap sm:-mx-5">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Approver</th>
                                <th>Pending</th>
                                <th>Overdue</th>
                                <th>Oldest Pending</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {queue.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <div className="flex items-center gap-2.5">
                                            <Avatar name={row.name} size="sm" />
                                            <div>
                                                <p className="font-medium text-slate-900">{row.name}</p>
                                                <p className="text-xs text-slate-500">{row.role}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{row.pending}</td>
                                    <td className={row.overdue > 0 ? 'font-medium text-danger' : ''}>
                                        {row.overdue}
                                    </td>
                                    <td>{formatDate(row.oldest_pending)}</td>
                                    <td>
                                        <Link
                                            href={route('timesheets.index')}
                                            className="text-sm font-medium text-brand hover:underline"
                                        >
                                            Review
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
