import { Link, router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { cn } from '@/utils/helpers';

const TOOL_KEYS = [
    { key: 'module', field: 'module_access', label: 'Module' },
    { key: 'schedule', field: 'schedule_access', label: 'Schedule' },
    { key: 'timesheet', field: 'timesheet_access', label: 'Timesheet' },
    { key: 'lms', field: 'lms_access', label: 'LMS' },
    { key: 'journey', field: 'journey_access', label: 'Journey' },
];

export default function WorkerDetailsPanel({ worker }) {
    if (!worker) {
        return (
            <Card className="h-full">
                <EmptyState
                    title="Select a worker"
                    description="Choose a row to view details, tool access, and assignments."
                />
            </Card>
        );
    }

    const toggleTool = (field, value) => {
        router.patch(
            route('workers.tools.update', worker.id),
            { [field]: value },
            { preserveScroll: true },
        );
    };

    return (
        <Card className="h-full">
            <div className="flex items-start gap-3">
                <Avatar name={worker.full_name} src={worker.avatar} size="lg" />
                <div className="min-w-0 flex-1">
                    <h3 className="truncate text-lg font-semibold text-slate-900">{worker.full_name}</h3>
                    <p className="text-sm text-slate-500">{worker.position || 'No position'}</p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Badge status={worker.status} />
                        {worker.on_site && <Badge tone="brand">On site</Badge>}
                    </div>
                </div>
            </div>

            <dl className="mt-5 space-y-3 text-sm">
                <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Company</dt>
                    <dd className="font-medium text-slate-900">{worker.company?.name || '—'}</dd>
                </div>
                <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Current project</dt>
                    <dd className="text-right font-medium text-slate-900">
                        {worker.primary_project?.name || '—'}
                    </dd>
                </div>
                <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Worker ID</dt>
                    <dd className="font-medium text-slate-900">{worker.employee_id || worker.id}</dd>
                </div>
                <div className="flex justify-between gap-3">
                    <dt className="text-slate-500">Company ID</dt>
                    <dd className="font-medium text-slate-900">{worker.company?.id || '—'}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Location</dt>
                    <dd className="inline-flex items-center gap-1 font-medium text-slate-900">
                        <MapPin className="h-3.5 w-3.5 text-slate-400" />
                        {worker.location || '—'}
                    </dd>
                </div>
            </dl>

            <div className="mt-6">
                <h4 className="mb-3 text-sm font-semibold text-slate-900">Tool access</h4>
                <div className="space-y-2">
                    {TOOL_KEYS.map((tool) => {
                        const enabled = Boolean(worker.tool_access?.[tool.key]);

                        return (
                            <label
                                key={tool.key}
                                className="flex min-h-10 items-center justify-between rounded-lg border border-slate-200 px-3"
                            >
                                <span className="text-sm text-slate-700">{tool.label}</span>
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={enabled}
                                    onClick={() => toggleTool(tool.field, !enabled)}
                                    className={cn(
                                        'relative h-6 w-11 rounded-full transition',
                                        enabled ? 'bg-brand' : 'bg-slate-200',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition',
                                            enabled ? 'left-5' : 'left-0.5',
                                        )}
                                    />
                                </button>
                            </label>
                        );
                    })}
                </div>
            </div>

            {Array.isArray(worker.assignments) && worker.assignments.length > 0 && (
                <div className="mt-6">
                    <h4 className="mb-3 text-sm font-semibold text-slate-900">Assigned projects</h4>
                    <ul className="space-y-2">
                        {worker.assignments.map((assignment) => (
                            <li
                                key={assignment.id}
                                className="rounded-lg border border-slate-200 px-3 py-2 text-sm"
                            >
                                <p className="font-medium text-slate-900">
                                    {assignment.project || assignment.project_code || 'Project'}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {[assignment.role, assignment.status].filter(Boolean).join(' · ') || 'Assigned'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="mt-6 flex gap-2">
                <Link href={route('workers.show', worker.id)} className="btn-secondary min-h-10 flex-1">
                    View
                </Link>
                <Link href={route('workers.edit', worker.id)} className="btn-primary min-h-10 flex-1">
                    Edit
                </Link>
            </div>
        </Card>
    );
}
