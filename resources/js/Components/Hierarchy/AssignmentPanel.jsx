import { Link } from '@inertiajs/react';
import { Building2, Info, Plus, X } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';

export default function AssignmentPanel({
    project,
    company,
    managers = [],
    onAddManager,
    onRemoveManager,
}) {
    return (
        <Card
            title={
                <span className="flex flex-col">
                    Assignment &amp; Reporting
                    <span className="text-xs font-normal text-slate-500">
                        {project?.name || 'Selected project'}
                    </span>
                </span>
            }
            actions={
                project && (
                    <Link
                        href={route('major-projects.show', project.id)}
                        className="text-sm font-medium text-brand hover:text-brand-hover"
                    >
                        View project
                    </Link>
                )
            }
        >
            <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                    <Building2 className="h-4 w-4" />
                </div>
                <div className="min-w-0">
                    <p className="text-xs uppercase tracking-wide text-slate-500">Major Project</p>
                    <p className="truncate text-sm font-semibold text-slate-900">
                        {project?.name || 'Not connected'}
                    </p>
                    <p className="truncate text-xs text-slate-500">{company?.name}</p>
                </div>
            </div>

            <p className="mt-4 text-sm text-slate-500">
                This Crew Hub reports to the following manager(s):
            </p>

            <ul className="mt-2 divide-y divide-slate-100">
                {managers.length === 0 && (
                    <li className="py-3 text-sm text-slate-500">No managers connected yet.</li>
                )}
                {managers.map((manager) => (
                    <li key={manager.id} className="flex items-center gap-3 py-2.5">
                        <Avatar name={manager.name} size="sm" />
                        <p className="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">
                            {manager.name}
                        </p>
                        <Badge tone={manager.relationship === 'primary' ? 'success' : 'brand'}>
                            {manager.relationship_label}
                        </Badge>
                        <button
                            type="button"
                            onClick={() => onRemoveManager(manager)}
                            className="rounded-md p-1.5 text-slate-400 transition hover:bg-danger-soft hover:text-danger"
                            aria-label={`Remove ${manager.name}`}
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </li>
                ))}
            </ul>

            <button
                type="button"
                onClick={onAddManager}
                className="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:text-brand-hover"
            >
                <Plus className="h-4 w-4" />
                Add Manager
            </button>

            <p className="mt-4 flex items-start gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                Only the connected manager(s) above will receive reports from this Crew Hub.
            </p>
        </Card>
    );
}
