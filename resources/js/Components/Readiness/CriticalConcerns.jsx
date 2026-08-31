import { AlertTriangle } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';

export default function CriticalConcerns({ concerns = [] }) {
    return (
        <Card padding={false} className="rounded-lg p-3">
            <div className="mb-2 flex items-center justify-between">
                <h3 className="flex items-center gap-1.5 text-[10px] font-bold text-slate-800">
                    <AlertTriangle className="h-3.5 w-3.5 text-danger" />
                    Critical Concerns
                </h3>
                <span className="text-[7px] font-semibold text-brand">View all</span>
            </div>
            {concerns.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No critical concerns"
                    description="High-severity issues will appear here."
                />
            ) : (
                <ul className="divide-y divide-slate-100">
                    {concerns.map((item) => (
                        <li key={item.id} className="grid grid-cols-[1fr_auto_auto] items-center gap-2 py-1.5 text-[7px]">
                            <span className="min-w-0 truncate text-slate-700">{item.title || item.issue}</span>
                            <span className="whitespace-nowrap text-slate-500">
                                {item.affected ?? 0} workers
                            </span>
                            <span
                                className={`min-w-10 rounded-full px-1.5 py-0.5 text-center font-semibold capitalize ${
                                    item.severity === 'critical'
                                        ? 'bg-danger-soft text-danger'
                                        : 'bg-warning-soft text-warning'
                                }`}
                            >
                                {item.severity || 'high'}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
