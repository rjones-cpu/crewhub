import { Link } from '@inertiajs/react';
import { Check, Minus } from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import { formatNumber } from '@/utils/formatters';

export default function AccountabilityMatrix({ rows = [], areas = [] }) {
    return (
        <Card
            className="h-full"
            title="Company Accountability Matrix"
            actions={
                <Link
                    href={route('workers.index')}
                    className="text-sm font-medium text-brand hover:text-brand-hover"
                >
                    View all
                </Link>
            }
        >
            <div className="-mx-4 table-wrap sm:-mx-5">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Workers</th>
                            {areas.map((area) => (
                                <th key={area}>{area}</th>
                            ))}
                            <th>Compliance Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={row.company}>
                                <td className="font-medium text-slate-900">{row.company}</td>
                                <td>{formatNumber(row.workers)}</td>
                                {areas.map((area) => (
                                    <td key={area}>
                                        {row.areas?.[area] ? (
                                            <Check className="h-4 w-4 text-success" />
                                        ) : (
                                            <Minus className="h-4 w-4 text-slate-300" />
                                        )}
                                    </td>
                                ))}
                                <td>
                                    <Badge tone={row.status === 'Accepted' ? 'success' : 'warning'}>
                                        {row.status}
                                    </Badge>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Card>
    );
}
