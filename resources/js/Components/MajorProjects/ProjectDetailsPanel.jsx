import { Link } from '@inertiajs/react';
import {
    Building2,
    CalendarCheck2,
    CalendarDays,
    CheckCircle2,
    EllipsisVertical,
    ExternalLink,
    FileText,
    Hash,
    Layers,
    MapPin,
    PencilLine,
    Users,
    UserSquare,
    X,
} from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import Badge from '@/Components/Shared/Badge';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import ProjectThumbnail from './ProjectThumbnail';
import { projectStatusLabel, PROJECT_MODULE_OPTIONS } from './projectHelpers';

function DetailRow({ icon: Icon, label, value }) {
    return (
        <div className="flex gap-2.5 py-1.5">
            <Icon className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" strokeWidth={1.8} />
            <div className="min-w-0">
                <p className="text-[11px] text-slate-500">{label}</p>
                <p className="mt-0.5 text-xs leading-relaxed text-slate-800">{value || '—'}</p>
            </div>
        </div>
    );
}

export default function ProjectDetailsPanel({ project, canManage = false, onClose }) {
    const modules = project.modules || {};

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[300px]">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Project Details</h3>
                <div className="flex items-center gap-0.5">
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button
                                type="button"
                                className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Project options"
                            >
                                <EllipsisVertical className="h-4 w-4" />
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content width="48" contentClasses="bg-white py-1">
                            <Link
                                href={route('major-projects.show', project.id)}
                                className="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                            >
                                <ExternalLink className="h-3.5 w-3.5" />
                                View dashboard
                            </Link>
                            {canManage && (
                                <Link
                                    href={route('major-projects.edit', project.id)}
                                    className="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                >
                                    <PencilLine className="h-3.5 w-3.5" />
                                    Edit project
                                </Link>
                            )}
                        </Dropdown.Content>
                    </Dropdown>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div className="flex-1 overflow-y-auto px-4 py-4">
                <div className="flex gap-3">
                    <ProjectThumbnail project={project} className="h-11 w-14" />
                    <div className="min-w-0">
                        <p className="text-sm font-semibold leading-snug text-slate-900">
                            {project.name}
                        </p>
                        <p className="mt-0.5 text-[11px] text-slate-500">
                            {project.project_number || project.code || '—'}
                        </p>
                        <Badge
                            status={project.status?.value || project.status}
                            className="mt-1.5"
                        >
                            {projectStatusLabel(project.status)}
                        </Badge>
                    </div>
                </div>

                <div className="mt-4 space-y-0.5">
                    <DetailRow icon={Building2} label="Client / Owner" value={project.company?.name} />
                    <DetailRow icon={Layers} label="Project Type" value={project.project_type} />
                    <DetailRow icon={UserSquare} label="Role" value={project.membership_role} />
                    <DetailRow icon={FileText} label="PO No." value={project.po_number} />
                    <DetailRow
                        icon={Hash}
                        label="Project Number"
                        value={project.project_number || project.code}
                    />
                    <DetailRow
                        icon={MapPin}
                        label="Address"
                        value={project.address || project.location}
                    />
                    <DetailRow
                        icon={CalendarDays}
                        label="Start Date"
                        value={formatDate(project.start_date)}
                    />
                    <DetailRow
                        icon={CalendarCheck2}
                        label="End Date (Estimated)"
                        value={formatDate(project.end_date)}
                    />
                    <DetailRow
                        icon={Users}
                        label="Assigned Workers"
                        value={formatNumber(project.workers_count)}
                    />
                    <DetailRow
                        icon={PencilLine}
                        label="Description"
                        value={project.comments || project.description}
                    />
                </div>

                <div className="mt-4 border-t border-slate-100 pt-4">
                    <p className="text-xs font-semibold text-slate-900">Assign Worker Requirements</p>
                    <div className="mt-2.5 space-y-2.5">
                        {PROJECT_MODULE_OPTIONS.map((module) => {
                            const enabled = Boolean(modules[module.key]);

                            return (
                                <div key={module.key} className="flex items-center justify-between gap-2">
                                    <span className="inline-flex items-center gap-2 text-xs text-slate-700">
                                        <CheckCircle2
                                            className={cn(
                                                'h-3.5 w-3.5',
                                                enabled ? 'text-success' : 'text-slate-300',
                                            )}
                                            strokeWidth={1.8}
                                        />
                                        {module.label}
                                    </span>
                                    <ToggleSwitch
                                        size="sm"
                                        checked={enabled}
                                        readOnly
                                        label={module.label}
                                    />
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            <div className="border-t border-slate-100 p-3">
                <Link
                    href={route('major-projects.show', project.id)}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border border-brand/40 bg-white px-3 py-2 text-xs font-medium text-brand transition hover:bg-brand-soft"
                >
                    View Project Dashboard
                    <ExternalLink className="h-3.5 w-3.5" />
                </Link>
            </div>
        </aside>
    );
}
