import { router } from '@inertiajs/react';
import {
    ArrowUpDown,
    BedDouble,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Clock,
    GraduationCap,
    Info,
    Plane,
    Search,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import EmptyState from '@/Components/Shared/EmptyState';
import Modal from '@/Components/Shared/Modal';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';
import InvitationDetailsPanel, {
    InvitationActionButtons,
    InvitationModulesList,
    InvitationStatusBadge,
} from './InvitationDetailsPanel';
import ProjectThumbnail from './ProjectThumbnail';
import {
    invitationStatusValue,
    INVITATION_STATUS_OPTIONS,
    PROJECT_MODULE_OPTIONS,
} from './projectHelpers';

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

const MODULE_ICONS = {
    Calendar,
    Clock,
    BedDouble,
    Plane,
    GraduationCap,
};

function InvitationPager({ links, onNavigate, perPage, onPerPageChange }) {
    return (
        <div className="flex items-center gap-2">
            <div className="flex items-center gap-1">
                {links.map((link, index) => {
                    const isPrev = index === 0;
                    const isNext = index === links.length - 1;
                    const label = link.label
                        .replace('&laquo; Previous', '')
                        .replace('Next &raquo;', '');

                    return (
                        <button
                            key={`${link.label}-${index}`}
                            type="button"
                            disabled={!link.url}
                            onClick={() => onNavigate(link.url)}
                            aria-label={isPrev ? 'Previous page' : isNext ? 'Next page' : `Page ${label}`}
                            className={cn(
                                'inline-flex h-7 min-w-[28px] items-center justify-center rounded-md border px-2 text-[11px] font-medium transition',
                                link.active
                                    ? 'border-brand bg-brand text-white'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                !link.url && 'cursor-not-allowed opacity-40',
                            )}
                        >
                            {isPrev ? (
                                <ChevronLeft className="h-3.5 w-3.5" />
                            ) : isNext ? (
                                <ChevronRight className="h-3.5 w-3.5" />
                            ) : (
                                label
                            )}
                        </button>
                    );
                })}
            </div>
            <select
                value={perPage}
                onChange={(event) => onPerPageChange(Number(event.target.value))}
                aria-label="Invitations per page"
                className="h-7 rounded-md border-slate-200 py-0 pl-2.5 pr-7 text-[11px] text-slate-600 focus:border-brand focus:ring-brand"
            >
                {PER_PAGE_OPTIONS.map((option) => (
                    <option key={option} value={option}>
                        {option} / page
                    </option>
                ))}
            </select>
        </div>
    );
}

function ModuleIcons({ modules = {} }) {
    const enabled = PROJECT_MODULE_OPTIONS.filter((module) => modules[module.key]);

    if (enabled.length === 0) {
        return <span className="text-slate-400">—</span>;
    }

    return (
        <div className="flex items-center gap-1.5 text-brand">
            {enabled.map((module) => {
                const Icon = MODULE_ICONS[module.icon];

                return Icon ? (
                    <span key={module.key} title={module.label}>
                        <Icon className="h-3.5 w-3.5" strokeWidth={1.8} />
                    </span>
                ) : null;
            })}
        </div>
    );
}

function ModalDetail({ label, value }) {
    return (
        <div>
            <p className="text-[11px] text-slate-500">{label}</p>
            <p className="mt-0.5 text-xs font-medium text-slate-800">{value || '—'}</p>
        </div>
    );
}

export default function JoinInvitationsPanel({ invitations, filters = {}, companies = [] }) {
    const { items, links, meta } = unwrapPaginated(invitations);
    const [search, setSearch] = useState(filters.search || '');
    const [selectedId, setSelectedId] = useState(null);
    const [modalId, setModalId] = useState(null);
    const [processingId, setProcessingId] = useState(null);
    const [bannerOpen, setBannerOpen] = useState(true);

    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];
    const total = meta?.total ?? items.length;

    const selected = useMemo(
        () => items.find((item) => item.id === selectedId) || null,
        [items, selectedId],
    );
    const modalInvitation = useMemo(
        () => items.find((item) => item.id === modalId) || null,
        [items, modalId],
    );

    const applyFilters = (next = {}) => {
        router.get(
            route('major-projects.join'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? 'all',
                company_id: next.company_id ?? filters.company_id ?? '',
                invited_on: next.invited_on ?? filters.invited_on ?? '',
                sort: next.sort ?? filters.sort ?? 'newest',
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    const respond = (invitation, action) => {
        setProcessingId(invitation.id);
        router.post(
            route(`major-projects.invitations.${action}`, invitation.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
                onSuccess: () => {
                    setSelectedId(null);
                    setModalId(null);
                },
            },
        );
    };

    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
            <div className="card min-w-0 flex-1">
                <div className="px-4 pt-4">
                    <h2 className="text-sm font-semibold text-slate-900">
                        Projects You&apos;ve Been Invited To
                    </h2>
                    <p className="mt-0.5 text-xs text-slate-500">
                        Review invitations from project owners and choose whether to join.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                    <div className="relative w-full max-w-[190px]">
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    applyFilters({ search });
                                }
                            }}
                            placeholder="Search invitations..."
                            className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                        />
                        <button
                            type="button"
                            onClick={() => applyFilters({ search })}
                            aria-label="Search invitations"
                            className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <Search className="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <select
                        className="input-field h-9 w-auto min-w-[110px] py-0 text-xs"
                        value={filters.status || 'all'}
                        onChange={(e) => applyFilters({ status: e.target.value, search })}
                    >
                        <option value="all">All Status</option>
                        {INVITATION_STATUS_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        className="input-field h-9 w-auto min-w-[130px] py-0 text-xs"
                        value={filters.company_id || ''}
                        onChange={(e) => applyFilters({ company_id: e.target.value, search })}
                    >
                        <option value="">All Companies</option>
                        {companies.map((company) => (
                            <option key={company.id} value={company.id}>
                                {company.name}
                            </option>
                        ))}
                    </select>
                    <input
                        type="date"
                        value={filters.invited_on || ''}
                        onChange={(e) => applyFilters({ invited_on: e.target.value, search })}
                        aria-label="Invitation date"
                        className="input-field h-9 w-auto py-0 text-xs"
                    />
                    <div className="relative">
                        <ArrowUpDown className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                        <select
                            className="input-field h-9 w-auto min-w-[130px] py-0 pl-8 text-xs"
                            value={filters.sort || 'newest'}
                            onChange={(e) => applyFilters({ sort: e.target.value, search })}
                        >
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                        </select>
                    </div>
                </div>

                {items.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            title="No invitations"
                            description="When a project owner invites your company, it will appear here."
                        />
                    </div>
                ) : (
                    <div className="table-wrap">
                        <table className="min-w-full text-xs">
                            <thead>
                                <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                    <th className="px-4 py-2.5 font-medium">Project Name</th>
                                    <th className="px-4 py-2.5 font-medium">Project Number</th>
                                    <th className="px-4 py-2.5 font-medium">Invited By</th>
                                    <th className="px-4 py-2.5 font-medium">Company / Owner</th>
                                    <th className="px-4 py-2.5 font-medium">Project Type</th>
                                    <th className="px-4 py-2.5 font-medium">Role</th>
                                    <th className="px-4 py-2.5 font-medium">Status</th>
                                    <th className="whitespace-nowrap px-4 py-2.5 font-medium">Invitation Date</th>
                                    <th className="whitespace-nowrap px-4 py-2.5 font-medium">Start Date</th>
                                    <th className="whitespace-nowrap px-4 py-2.5 font-medium">End Date</th>
                                    <th className="px-4 py-2.5 font-medium">Workers</th>
                                    <th className="px-4 py-2.5 font-medium">Modules Enabled</th>
                                    <th className="px-4 py-2.5 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {items.map((invitation) => {
                                    const project = invitation.project || {};
                                    const pending = invitationStatusValue(invitation.status) === 'pending';

                                    return (
                                        <tr
                                            key={invitation.id}
                                            className={cn(
                                                'cursor-pointer align-middle transition hover:bg-slate-50/70',
                                                selectedId === invitation.id && 'bg-brand-soft/40',
                                            )}
                                            onClick={() => setSelectedId(invitation.id)}
                                        >
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center gap-3">
                                                    <ProjectThumbnail project={project} />
                                                    <div className="min-w-0">
                                                        <p className="font-semibold leading-snug text-slate-900">
                                                            {project.name || '—'}
                                                        </p>
                                                        <p className="mt-0.5 text-[11px] text-slate-500">
                                                            {project.project_number || project.code || '—'}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                {project.project_number || project.code || '—'}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <span className="inline-flex items-center gap-2">
                                                    <Avatar
                                                        name={invitation.inviter?.name}
                                                        src={invitation.inviter?.avatar}
                                                        size="xs"
                                                        className="ring-0"
                                                    />
                                                    <span className="text-slate-700">
                                                        {invitation.inviter?.name || '—'}
                                                    </span>
                                                </span>
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                {project.company?.name || '—'}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                {project.project_type || '—'}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                {invitation.role || '—'}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <InvitationStatusBadge status={invitation.status} />
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                                {formatDate(invitation.invited_at)}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                                {formatDate(project.start_date)}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                                {formatDate(project.end_date)}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-600">
                                                {formatNumber(project.workers_count)}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <ModuleIcons modules={project.modules} />
                                            </td>
                                            <td
                                                className="px-4 py-2.5"
                                                onClick={(event) => event.stopPropagation()}
                                            >
                                                <InvitationActionButtons
                                                    pending={pending}
                                                    processing={processingId === invitation.id}
                                                    stacked
                                                    onViewDetails={() => {
                                                        setSelectedId(invitation.id);
                                                        setModalId(invitation.id);
                                                    }}
                                                    onAccept={() => respond(invitation, 'accept')}
                                                    onDecline={() => respond(invitation, 'decline')}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                {items.length > 0 && (
                    <>
                        <div className="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-[11px] text-slate-500">
                                Showing {meta?.from ?? 0} to {meta?.to ?? items.length} of{' '}
                                {formatNumber(total)} invitations
                            </p>
                            <InvitationPager
                                links={pageLinks}
                                perPage={meta?.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0]}
                                onPerPageChange={(value) => applyFilters({ search, per_page: value })}
                                onNavigate={(url) =>
                                    url && router.get(url, {}, { preserveState: true, preserveScroll: true })
                                }
                            />
                        </div>
                        {bannerOpen && (
                            <div className="flex items-start gap-2 border-t border-brand/10 bg-brand-soft px-4 py-2.5 text-xs text-brand">
                                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                <p className="flex-1">
                                    Accepting the invitation will move this project into Current Projects.
                                </p>
                                <button
                                    type="button"
                                    onClick={() => setBannerOpen(false)}
                                    className="rounded p-0.5 text-brand/70 hover:bg-white/50 hover:text-brand"
                                    aria-label="Dismiss"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            </div>
                        )}
                    </>
                )}
            </div>

            {selected && (
                <InvitationDetailsPanel
                    invitation={selected}
                    processing={processingId === selected.id}
                    onClose={() => setSelectedId(null)}
                    onAccept={() => respond(selected, 'accept')}
                    onDecline={() => respond(selected, 'decline')}
                />
            )}

            <Modal
                show={Boolean(modalInvitation)}
                onClose={() => setModalId(null)}
                title="View Invitation Details"
                maxWidth="4xl"
            >
                {modalInvitation && (
                    <InvitationDetailsModal
                        invitation={modalInvitation}
                        processing={processingId === modalInvitation.id}
                        onClose={() => setModalId(null)}
                        onAccept={() => respond(modalInvitation, 'accept')}
                        onDecline={() => respond(modalInvitation, 'decline')}
                    />
                )}
            </Modal>
        </div>
    );
}

function InvitationDetailsModal({ invitation, processing, onClose, onAccept, onDecline }) {
    const project = invitation.project || {};
    const pending = invitationStatusValue(invitation.status) === 'pending';

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-5 lg:flex-row">
                <div className="flex shrink-0 gap-3 lg:w-56">
                    <ProjectThumbnail project={project} className="h-14 w-[72px]" />
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
                <div className="grid min-w-0 flex-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    <ModalDetail label="Invited By" value={invitation.inviter?.name} />
                    <ModalDetail label="Start Date" value={formatDate(project.start_date)} />
                    <ModalDetail label="PO No." value={project.po_number} />
                    <ModalDetail label="Company / Owner" value={project.company?.name} />
                    <ModalDetail label="End Date" value={formatDate(project.end_date)} />
                    <ModalDetail label="Assign Manager" value={project.manager?.name} />
                    <ModalDetail label="Project Type" value={project.project_type} />
                    <ModalDetail label="Workers" value={formatNumber(project.workers_count)} />
                    <ModalDetail label="Address" value={project.address} />
                    <ModalDetail label="Role Requested" value={invitation.role} />
                    <ModalDetail label="Invitation Date" value={formatDate(invitation.invited_at)} />
                    <ModalDetail
                        label="Comments"
                        value={project.comments || project.description}
                    />
                </div>
            </div>

            <div className="border-t border-slate-100 pt-4">
                <p className="mb-2 text-xs font-semibold text-slate-900">Modules Enabled</p>
                <InvitationModulesList modules={project.modules} layout="row" />
            </div>

            <div className="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button
                    type="button"
                    onClick={onClose}
                    className="inline-flex h-9 items-center justify-center rounded-md px-3 text-xs font-medium text-slate-600 hover:bg-slate-50"
                >
                    Close
                </button>
                {pending && (
                    <>
                        <button
                            type="button"
                            disabled={processing}
                            onClick={onDecline}
                            className="inline-flex h-9 items-center justify-center rounded-md border border-danger/40 bg-white px-3 text-xs font-medium text-danger hover:bg-danger-soft disabled:opacity-50"
                        >
                            Decline Invite
                        </button>
                        <button
                            type="button"
                            disabled={processing}
                            onClick={onAccept}
                            className="inline-flex h-9 items-center justify-center rounded-md bg-brand px-3 text-xs font-medium text-white hover:bg-brand-hover disabled:opacity-50"
                        >
                            Accept Invite
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}
