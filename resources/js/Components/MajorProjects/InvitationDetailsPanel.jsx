import {
    Building2,
    CalendarCheck2,
    CalendarDays,
    CheckCircle2,
    FileText,
    Layers,
    Mail,
    MapPin,
    PencilLine,
    Users,
    UserSquare,
    X,
} from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import ProjectThumbnail from './ProjectThumbnail';
import {
    invitationStatusLabel,
    invitationStatusTone,
    invitationStatusValue,
    PROJECT_MODULE_OPTIONS,
} from './projectHelpers';

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

export function InvitationActionButtons({
    pending,
    processing,
    stacked = false,
    fullWidth = false,
    onAccept,
    onDecline,
    onViewDetails,
}) {
    return (
        <div
            className={cn(
                stacked ? 'flex flex-col items-stretch gap-1.5' : 'flex flex-wrap items-center justify-end gap-2',
                fullWidth && 'w-full',
            )}
        >
            {onViewDetails && (
                <button
                    type="button"
                    onClick={onViewDetails}
                    className="inline-flex h-8 items-center justify-center rounded-md border border-slate-200 bg-white px-2.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                >
                    View Details
                </button>
            )}
            {pending && (
                <>
                    <button
                        type="button"
                        disabled={processing}
                        onClick={onAccept}
                        className={cn(
                            'inline-flex h-8 items-center justify-center rounded-md bg-brand px-2.5 text-[11px] font-medium text-white hover:bg-brand-hover disabled:opacity-50',
                            fullWidth && 'h-9 text-xs',
                        )}
                    >
                        Accept Invite
                    </button>
                    <button
                        type="button"
                        disabled={processing}
                        onClick={onDecline}
                        className={cn(
                            'inline-flex h-8 items-center justify-center rounded-md border border-danger/40 bg-white px-2.5 text-[11px] font-medium text-danger hover:bg-danger-soft disabled:opacity-50',
                            fullWidth && 'h-9 text-xs',
                        )}
                    >
                        Decline Invite
                    </button>
                </>
            )}
        </div>
    );
}

export function InvitationStatusBadge({ status }) {
    return (
        <Badge tone={invitationStatusTone(status)}>
            {invitationStatusLabel(status)}
        </Badge>
    );
}

export function InvitationModulesList({ modules = {}, layout = 'list' }) {
    const enabled = PROJECT_MODULE_OPTIONS.filter((module) => modules[module.key]);

    if (enabled.length === 0) {
        return <p className="text-xs text-slate-500">None</p>;
    }

    return (
        <ul
            className={cn(
                layout === 'row'
                    ? 'flex flex-wrap gap-x-5 gap-y-2'
                    : 'space-y-2',
            )}
        >
            {enabled.map((module) => (
                <li key={module.key} className="inline-flex items-center gap-1.5 text-xs text-slate-700">
                    <CheckCircle2 className="h-3.5 w-3.5 text-success" strokeWidth={1.8} />
                    {module.label}
                </li>
            ))}
        </ul>
    );
}

export default function InvitationDetailsPanel({
    invitation,
    processing = false,
    onClose,
    onAccept,
    onDecline,
}) {
    const project = invitation.project || {};
    const pending = invitationStatusValue(invitation.status) === 'pending';

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[300px]">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Invitation Details</h3>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>

            <div className="flex-1 overflow-y-auto px-4 py-4">
                <div className="flex gap-3">
                    <ProjectThumbnail project={project} className="h-11 w-14" />
                    <div className="min-w-0">
                        <p className="text-sm font-semibold leading-snug text-slate-900">
                            {project.name || '—'}
                        </p>
                        <p className="mt-0.5 text-[11px] text-slate-500">
                            {project.project_number || project.code || '—'}
                        </p>
                        <div className="mt-1.5">
                            <InvitationStatusBadge status={invitation.status} />
                        </div>
                    </div>
                </div>

                <div className="mt-4 space-y-0.5">
                    <DetailRow icon={Mail} label="Invited By" value={invitation.inviter?.name} />
                    <DetailRow icon={Building2} label="Company / Owner" value={project.company?.name} />
                    <DetailRow icon={Layers} label="Project Type" value={project.project_type} />
                    <DetailRow icon={UserSquare} label="Role Requested" value={invitation.role} />
                    <DetailRow
                        icon={CalendarDays}
                        label="Invitation Date"
                        value={formatDate(invitation.invited_at)}
                    />
                    <DetailRow
                        icon={CalendarDays}
                        label="Start Date"
                        value={formatDate(project.start_date)}
                    />
                    <DetailRow
                        icon={CalendarCheck2}
                        label="End Date"
                        value={formatDate(project.end_date)}
                    />
                    <DetailRow
                        icon={Users}
                        label="Workers"
                        value={formatNumber(project.workers_count)}
                    />
                    <DetailRow icon={FileText} label="PO No." value={project.po_number} />
                    <DetailRow icon={UserSquare} label="Assign Manager" value={project.manager?.name} />
                    <DetailRow icon={MapPin} label="Address" value={project.address} />
                    <DetailRow
                        icon={PencilLine}
                        label="Comments"
                        value={project.comments || project.description}
                    />
                </div>

                <div className="mt-4 border-t border-slate-100 pt-4">
                    <p className="text-xs font-semibold text-slate-900">Modules Enabled</p>
                    <div className="mt-2.5">
                        <InvitationModulesList modules={project.modules} />
                    </div>
                </div>
            </div>

            {pending && (
                <div className="border-t border-slate-100 p-3">
                    <InvitationActionButtons
                        pending
                        processing={processing}
                        stacked
                        fullWidth
                        onAccept={onAccept}
                        onDecline={onDecline}
                    />
                </div>
            )}
        </aside>
    );
}
