import { Link } from '@inertiajs/react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import { formatDate } from '@/utils/formatters';
import { unwrapPaginated } from '@/utils/helpers';

export default function WorkersRequiringAttention({ attention }) {
    const { items, links, meta } = unwrapPaginated(attention);

    return (
        <Card padding={false} className="overflow-hidden rounded-lg">
            <div className="flex items-center justify-between px-3 py-2.5">
                <h3 className="text-[10px] font-bold text-slate-800">Workers Requiring Attention</h3>
                <span className="text-[7px] font-semibold text-brand">View all</span>
            </div>
            {items.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="All clear"
                    description="No workers currently need attention."
                />
            ) : (
                <div className="table-wrap">
                    <table className="readiness-attention-table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Company</th>
                                <th>Primary Project</th>
                                <th>Issue</th>
                                <th>Due Date</th>
                                <th>Readiness Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {items.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <span className="flex items-center gap-2">
                                            <Avatar
                                                name={row.worker}
                                                src={row.avatar}
                                                size="sm"
                                                className="h-6 w-6 text-[8px] ring-0"
                                            />
                                            <span className="min-w-0">
                                                <Link
                                                    href={route('workers.show', row.worker_id || row.id)}
                                                    className="block truncate font-semibold text-slate-800 hover:text-brand"
                                                >
                                                    {row.worker || row.employee_id}
                                                </Link>
                                                <span className="block text-[7px] text-slate-400">
                                                    ID: {row.employee_id || '—'}
                                                </span>
                                            </span>
                                        </span>
                                    </td>
                                    <td>{row.company || '—'}</td>
                                    <td>{row.primary_project || '—'}</td>
                                    <td>
                                        <span className={`mr-1 inline-block h-1.5 w-1.5 rounded-full ${
                                            row.status === 'not_ready' ? 'bg-danger' : 'bg-warning'
                                        }`} />
                                        {row.issue}
                                    </td>
                                    <td>
                                        <span className="block">{formatDate(row.due_date)}</span>
                                        <span className="block text-[7px] text-slate-400">{row.due_relative}</span>
                                    </td>
                                    <td>
                                        <Badge status={row.status} />
                                    </td>
                                    <td>
                                        <Link
                                            href={route('workers.show', row.worker_id || row.id)}
                                            className="inline-flex rounded border border-brand/30 px-2 py-1 font-semibold text-brand hover:bg-brand-soft"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <div className="px-3 pb-2.5">
                <Pagination links={links} meta={meta} compact />
            </div>
        </Card>
    );
}
