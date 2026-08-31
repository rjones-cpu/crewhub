import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import { formatNumber } from '@/utils/formatters';
import PanelTitle from './PanelTitle';

export default function KeyExceptionsPanel({ exceptions = [], onViewAll }) {
    return (
        <Card
            title={
                <PanelTitle icon={AlertTriangle}>Key Exceptions (Attention Required)</PanelTitle>
            }
            className="flex h-full flex-col"
        >
            <div className="table-wrap flex-1">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Issue Type</th>
                            <th>Count</th>
                            <th>Details</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {exceptions.map((exception) => (
                            <tr key={exception.id}>
                                <td className="whitespace-normal font-medium text-slate-900">
                                    {exception.href ? (
                                        <Link href={exception.href} className="hover:text-brand hover:underline">
                                            {exception.issue}
                                        </Link>
                                    ) : (
                                        exception.issue
                                    )}
                                </td>
                                <td className="text-slate-700">{formatNumber(exception.count)}</td>
                                <td className="whitespace-normal text-slate-500">
                                    {exception.details}
                                </td>
                                <td>
                                    <Badge status={exception.priority} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <button
                type="button"
                onClick={onViewAll}
                className="mt-3 inline-flex items-center gap-1 self-start text-xs font-medium text-brand hover:underline"
            >
                View all exceptions
                <ArrowRight className="h-3 w-3" />
            </button>
        </Card>
    );
}
