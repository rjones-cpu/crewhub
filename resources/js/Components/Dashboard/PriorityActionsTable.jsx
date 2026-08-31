import { AlertCircle, AlertTriangle, Info } from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate, formatNumber } from '@/utils/formatters';

const SEVERITY_ICONS = {
    critical: { icon: AlertCircle, className: 'text-danger' },
    high: { icon: AlertCircle, className: 'text-danger' },
    medium: { icon: AlertTriangle, className: 'text-warning' },
    low: { icon: Info, className: 'text-brand' },
};

export default function PriorityActionsTable({ actions = [] }) {
    return (
        <Card title="Priority Actions" className="flex h-full flex-col">
            {actions.length === 0 ? (
                <EmptyState
                    className="py-8"
                    title="No priority actions"
                    description="You're all caught up."
                />
            ) : (
                <div className="no-scrollbar -mx-4 -mb-4 min-h-0 flex-1 overflow-auto sm:-mx-5 sm:-mb-5">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Issue</th>
                                <th>Affected</th>
                                <th>Owner</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {actions.map((action) => {
                                const severity = SEVERITY_ICONS[action.severity] || SEVERITY_ICONS.low;
                                const SeverityIcon = severity.icon;

                                return (
                                    <tr key={action.id}>
                                        <td>
                                            <div className="flex max-w-[260px] items-start gap-2">
                                                <SeverityIcon
                                                    className={`mt-0.5 h-4 w-4 shrink-0 ${severity.className}`}
                                                />
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium text-slate-900">
                                                        {action.issue || action.title}
                                                    </p>
                                                    {action.project && (
                                                        <p className="truncate text-xs text-slate-500">
                                                            {action.project}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {action.affected
                                                ? formatNumber(action.affected)
                                                : '—'}
                                        </td>
                                        <td>{action.owner || '—'}</td>
                                        <td>{formatDate(action.due_date)}</td>
                                        <td>
                                            <Badge status={action.severity || action.status} />
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </Card>
    );
}
