import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    EllipsisVertical,
    ExternalLink,
    Eye,
    LayoutGrid,
    List,
    Search,
    UserPlus,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import Dropdown from '@/Components/Dropdown';
import Badge from '@/Components/Shared/Badge';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';
import ProjectDetailsPanel from './ProjectDetailsPanel';
import ProjectThumbnail from './ProjectThumbnail';
import {
    projectStatusDotClass,
    projectStatusLabel,
    PROJECT_STATUS_OPTIONS,
} from './projectHelpers';

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

function InviteCompaniesDialog({ project, companies, onClose }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        company_ids: [],
    });
    const availableCompanies = companies.filter(
        (company) => String(company.id) !== String(project.company_id),
    );

    const toggleCompany = (companyId) => {
        const id = String(companyId);
        const selected = data.company_ids.map(String);

        setData(
            'company_ids',
            selected.includes(id)
                ? selected.filter((selectedId) => selectedId !== id)
                : [...selected, id],
        );
    };

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('major-projects.invitations.store', project.id), {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return createPortal(
        <div className="fixed inset-0 z-[70] grid place-items-center bg-slate-950/45 p-4" onMouseDown={close}>
            <form
                onSubmit={submit}
                onMouseDown={(event) => event.stopPropagation()}
                className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="invite-companies-title"
            >
                <div className="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 id="invite-companies-title" className="font-semibold text-slate-900">
                            Invite companies
                        </h2>
                        <p className="mt-1 text-xs text-slate-500">
                            Invite companies to join {project.name} as contractors.
                        </p>
                    </div>
                    <button type="button" onClick={close} className="rounded p-1 text-slate-400 hover:bg-slate-100">
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="max-h-72 space-y-2 overflow-y-auto px-5 py-4">
                    {availableCompanies.length === 0 ? (
                        <p className="text-sm text-slate-500">No other companies are available.</p>
                    ) : (
                        availableCompanies.map((company) => (
                            <label
                                key={company.id}
                                className="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 hover:bg-slate-50"
                            >
                                <input
                                    type="checkbox"
                                    checked={data.company_ids.map(String).includes(String(company.id))}
                                    onChange={() => toggleCompany(company.id)}
                                    className="rounded border-slate-300 text-brand focus:ring-brand"
                                />
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-medium text-slate-800">
                                        {company.name}
                                    </span>
                                    {company.code && (
                                        <span className="block text-[11px] text-slate-500">{company.code}</span>
                                    )}
                                </span>
                            </label>
                        ))
                    )}
                    {errors.company_ids && <p className="text-xs text-rose-600">{errors.company_ids}</p>}
                </div>

                <div className="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                    <button type="button" onClick={close} className="btn-secondary">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={processing || data.company_ids.length === 0}
                        className="btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? 'Sending...' : 'Send invitation'}
                    </button>
                </div>
            </form>
        </div>,
        document.body,
    );
}

function StatusCell({ status }) {
    return (
        <span className="inline-flex items-center gap-1.5 whitespace-nowrap text-slate-700">
            <span className={cn('h-1.5 w-1.5 rounded-full', projectStatusDotClass(status))} />
            {projectStatusLabel(status)}
        </span>
    );
}

/**
 * Page controls for the listing footer. The shared Pagination component hides
 * itself on a single page, but this listing always shows the pager next to the
 * per-page selector.
 */
function ListingPager({ links, onNavigate, perPage, onPerPageChange }) {
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
                aria-label="Projects per page"
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

export default function CurrentProjectsPanel({
    projects,
    filters = {},
    clients = [],
    canManage = false,
    canCreate = false,
    canAttemptCreate = false,
    isSuperAdmin = false,
}) {
    const { items, links, meta } = unwrapPaginated(projects);
    const [view, setView] = useState('list');
    const [selectedId, setSelectedId] = useState(null);
    const [inviteProject, setInviteProject] = useState(null);
    const [search, setSearch] = useState(filters.search || '');

    // Resource collections expose page links under meta; plain paginators use links.
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];
    const total = meta?.total ?? items.length;
    const direction = filters.direction === 'desc' ? 'desc' : 'asc';

    const selected = useMemo(
        () => items.find((p) => p.id === selectedId) || null,
        [items, selectedId],
    );

    const applyFilters = (next = {}) => {
        router.get(
            route('major-projects.index'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? '',
                client_id: next.client_id ?? filters.client_id ?? '',
                direction: next.direction ?? direction,
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
            <div className="card min-w-0 flex-1">
                <div className="flex items-center gap-2 px-4 pt-4">
                    <h2 className="text-sm font-semibold text-slate-900">My Current Projects</h2>
                    <span className="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-slate-100 px-1.5 text-[11px] font-semibold text-slate-600">
                        {formatNumber(total)}
                    </span>
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
                            placeholder="Search projects..."
                            className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                        />
                        <button
                            type="button"
                            onClick={() => applyFilters({ search })}
                            aria-label="Search projects"
                            className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <Search className="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <select
                        className="input-field h-9 w-auto min-w-[110px] py-0 text-xs"
                        value={filters.status || ''}
                        onChange={(e) => applyFilters({ status: e.target.value, search })}
                    >
                        <option value="">All Status</option>
                        {PROJECT_STATUS_OPTIONS.map((value) => (
                            <option key={value} value={value}>
                                {projectStatusLabel(value)}
                            </option>
                        ))}
                    </select>
                    {clients.length > 0 && (
                        <select
                            className="input-field h-9 w-auto min-w-[110px] py-0 text-xs"
                            value={filters.client_id || ''}
                            onChange={(e) => applyFilters({ client_id: e.target.value, search })}
                        >
                            <option value="">All Clients</option>
                            {clients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </select>
                    )}
                    <div className="ml-auto flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setView('card')}
                            className={cn(
                                'inline-flex h-9 items-center gap-1.5 rounded-lg border px-3 text-xs font-medium transition',
                                view === 'card'
                                    ? 'border-brand bg-brand-soft text-brand'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            <LayoutGrid className="h-3.5 w-3.5" />
                            Card View
                        </button>
                        <button
                            type="button"
                            onClick={() => setView('list')}
                            className={cn(
                                'inline-flex h-9 items-center gap-1.5 rounded-lg border px-3 text-xs font-medium transition',
                                view === 'list'
                                    ? 'border-brand bg-brand-soft text-brand'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            <List className="h-3.5 w-3.5" />
                            List View
                        </button>
                    </div>
                </div>

                {items.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            title="No major projects yet"
                            description="Your organization does not currently own or participate in any major projects. Create a new project or wait for an invitation."
                            action={
                                canAttemptCreate ? (
                                    <Link
                                        href={route('major-projects.create')}
                                        className="btn-primary inline-flex"
                                    >
                                        Create Major Project
                                    </Link>
                                ) : null
                            }
                        />
                    </div>
                ) : view === 'list' ? (
                    <div className="table-wrap">
                        <table className="min-w-full text-xs">
                            <thead>
                                <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                    <th className="px-4 py-2.5 font-medium">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                applyFilters({
                                                    search,
                                                    direction: direction === 'asc' ? 'desc' : 'asc',
                                                })
                                            }
                                            className="inline-flex items-center gap-1 hover:text-slate-700"
                                        >
                                            Project Name
                                            <ArrowUpDown className="h-3 w-3 text-slate-400" />
                                        </button>
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">Client / Owner</th>
                                    <th className="px-4 py-2.5 font-medium">Project Type</th>
                                    <th className="px-4 py-2.5 font-medium">Role</th>
                                    <th className="px-4 py-2.5 font-medium">Status</th>
                                    <th className="whitespace-nowrap px-4 py-2.5 font-medium">Start Date</th>
                                    <th className="whitespace-nowrap px-4 py-2.5 font-medium">End Date</th>
                                    <th className="px-4 py-2.5 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {items.map((project) => (
                                    <tr
                                        key={project.id}
                                        className={cn(
                                            'cursor-pointer align-middle transition hover:bg-slate-50/70',
                                            selectedId === project.id && 'bg-brand-soft/40',
                                        )}
                                        onClick={() => setSelectedId(project.id)}
                                    >
                                        <td className="px-4 py-2.5">
                                            <div className="flex items-center gap-3">
                                                <ProjectThumbnail project={project} />
                                                <div className="min-w-0">
                                                    <p className="font-semibold leading-snug text-slate-900">
                                                        {project.name}
                                                    </p>
                                                    <p className="mt-0.5 text-[11px] text-slate-500">
                                                        {project.project_number || project.code || '—'}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 text-slate-600">
                                            {project.company?.name || '—'}
                                        </td>
                                        <td className="px-4 py-2.5 text-slate-600">
                                            {project.project_type || '—'}
                                        </td>
                                        <td className="px-4 py-2.5">
                                            {project.membership_role ? (
                                                <Badge tone="brand">{project.membership_role}</Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-2.5">
                                            <StatusCell status={project.status} />
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                            {formatDate(project.start_date)}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                            {formatDate(project.end_date)}
                                        </td>
                                        <td
                                            className="px-4 py-2.5"
                                            onClick={(event) => event.stopPropagation()}
                                        >
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <button
                                                        type="button"
                                                        className="inline-grid h-7 w-7 place-items-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                        aria-label={`Actions for ${project.name}`}
                                                    >
                                                        <EllipsisVertical className="h-4 w-4" />
                                                    </button>
                                                </Dropdown.Trigger>
                                                <Dropdown.Content width="48" contentClasses="bg-white py-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelectedId(project.id)}
                                                        className="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        View details
                                                    </button>
                                                    <Link
                                                        href={route('major-projects.show', project.id)}
                                                        className="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                    >
                                                        <ExternalLink className="h-3.5 w-3.5" />
                                                        View dashboard
                                                    </Link>
                                                    {isSuperAdmin && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setInviteProject(project)}
                                                            className="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                        >
                                                            <UserPlus className="h-3.5 w-3.5" />
                                                            Invite company
                                                        </button>
                                                    )}
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="grid gap-3 border-t border-slate-100 p-4 sm:grid-cols-2 xl:grid-cols-3">
                        {items.map((project) => (
                            <button
                                key={project.id}
                                type="button"
                                onClick={() => setSelectedId(project.id)}
                                className={cn(
                                    'rounded-xl border p-4 text-left transition',
                                    selectedId === project.id
                                        ? 'border-brand bg-brand-soft/30'
                                        : 'border-slate-200 bg-white hover:border-slate-300',
                                )}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <ProjectThumbnail project={project} className="h-10 w-12" />
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-slate-900">
                                                {project.name}
                                            </p>
                                            <p className="text-[11px] text-slate-500">
                                                {project.project_number || project.code}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge status={project.status?.value || project.status}>
                                        {projectStatusLabel(project.status)}
                                    </Badge>
                                </div>
                                <p className="mt-3 text-sm text-slate-600">
                                    {project.company?.name || '—'}
                                </p>
                                <p className="mt-1 text-xs text-slate-500">
                                    {formatNumber(project.workers_count)} workers ·{' '}
                                    {formatDate(project.start_date)} – {formatDate(project.end_date)}
                                </p>
                            </button>
                        ))}
                    </div>
                )}

                {items.length > 0 && (
                    <div className="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-[11px] text-slate-500">
                            Showing {meta?.from ?? 0} to {meta?.to ?? items.length} of{' '}
                            {formatNumber(total)} projects
                        </p>
                        <ListingPager
                            links={pageLinks}
                            perPage={meta?.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0]}
                            onPerPageChange={(value) => applyFilters({ search, per_page: value })}
                            onNavigate={(url) =>
                                url && router.get(url, {}, { preserveState: true, preserveScroll: true })
                            }
                        />
                    </div>
                )}
            </div>

            {selected && (
                <ProjectDetailsPanel
                    project={selected}
                    canManage={canManage}
                    onClose={() => setSelectedId(null)}
                />
            )}
            {inviteProject && (
                <InviteCompaniesDialog
                    project={inviteProject}
                    companies={clients}
                    onClose={() => setInviteProject(null)}
                />
            )}
        </div>
    );
}
