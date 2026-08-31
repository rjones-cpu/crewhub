import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';

export default function MissingByCompany({ items = [] }) {
    return (
        <Card title="Missing by Company" className="h-full">
            {items.length === 0 ? (
                <EmptyState title="No missing sheets" description="Every company is up to date." />
            ) : (
                <ul className="space-y-3">
                    {items.map((row) => (
                        <li key={row.company}>
                            <div className="mb-1 flex items-center justify-between gap-2 text-sm">
                                <span className="font-medium text-slate-800">{row.company}</span>
                                <span className="text-slate-500">{row.count}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className="h-full rounded-full bg-danger"
                                    style={{ width: `${Math.min(100, row.pct || 0)}%` }}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
