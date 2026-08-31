import { Link } from '@inertiajs/react';
import { AlertCircle, AlertTriangle, Info } from 'lucide-react';
import EmptyState from '@/Components/Shared/EmptyState';
import { statusLabel } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import CardFooterLink from './CardFooterLink';

const SEVERITIES = {
    critical: { icon: AlertCircle, icon_class: 'text-danger', pill: 'bg-danger-soft text-danger' },
    high: { icon: AlertCircle, icon_class: 'text-danger', pill: 'bg-danger-soft text-danger' },
    medium: { icon: AlertTriangle, icon_class: 'text-warning', pill: 'bg-warning-soft text-warning' },
    low: { icon: Info, icon_class: 'text-brand', pill: 'bg-brand-soft text-brand' },
};

export default function TopPriorityActionsCard({ actions = [], href }) {
    return (
        <div className="card flex h-full flex-col p-4">
            <div className="flex items-center justify-between gap-2">
                <h3 className="section-title">Top Priority Actions</h3>
                <Link
                    href={href}
                    className="text-[10px] font-medium text-brand transition hover:text-brand-hover"
                >
                    View all
                </Link>
            </div>

            {actions.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No priority actions"
                    description="You're all caught up."
                />
            ) : (
                <ul className="mt-2 min-h-0 flex-1 divide-y divide-slate-100">
                    {actions.map((action) => {
                        const severity = SEVERITIES[action.severity] || SEVERITIES.low;
                        const SeverityIcon = severity.icon;

                        return (
                            <li key={action.id} className="flex items-start gap-2 py-1.5">
                                <SeverityIcon
                                    className={cn('mt-0.5 h-3 w-3 shrink-0', severity.icon_class)}
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[10px] font-medium text-slate-800">
                                        {action.issue || action.title}
                                    </p>
                                    {action.project && (
                                        <p className="truncate text-[9px] text-slate-500">
                                            {action.project}
                                        </p>
                                    )}
                                </div>
                                <span
                                    className={cn(
                                        'shrink-0 rounded px-1.5 py-0.5 text-[9px] font-medium',
                                        severity.pill,
                                    )}
                                >
                                    {statusLabel(action.severity)}
                                </span>
                                <span className="w-[62px] shrink-0 text-right text-[9px] text-slate-500">
                                    {action.affected_label}
                                </span>
                            </li>
                        );
                    })}
                </ul>
            )}

            <CardFooterLink href={href}>View all actions</CardFooterLink>
        </div>
    );
}
