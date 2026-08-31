import { Head, router } from '@inertiajs/react';
import {
    CircleCheck,
    MapPin,
    Pencil,
    Plus,
    Search,
    Share2,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import HubFormPanel from '@/Components/Journeys/Hubs/HubFormPanel';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from '@/Components/Journeys/ListingPager';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';

function StatCard({ label, value, hint, icon: Icon, tone }) {
    const tones = {
        brand: 'bg-brand-soft text-brand',
        success: 'bg-success-soft text-success',
        warning: 'bg-warning-soft text-warning',
        danger: 'bg-danger-soft text-danger',
    };

    return (
        <div className="card flex items-center justify-between px-4 py-3">
            <div>
                <p className="text-xs font-medium text-slate-500">{label}</p>
                <p className="mt-1 text-2xl font-semibold text-slate-900">{formatNumber(value)}</p>
                <p className="mt-0.5 text-[11px] text-slate-400">{hint}</p>
            </div>
            <span className={cn('grid h-9 w-9 place-items-center rounded-lg', tones[tone])}>
                <Icon className="h-4 w-4" strokeWidth={1.8} />
            </span>
        </div>
    );
}

export default function JourneyHubsIndex({
    hubs,
    undesignated,
    stats = {},
    filters = {},
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(hubs);
    const { items: pending } = unwrapPaginated(undesignated);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];

    const [search, setSearch] = useState(filters.search || '');
    const [editing, setEditing] = useState(null);
    const [showPanel, setShowPanel] = useState(false);
    const [selectedJourneys, setSelectedJourneys] = useState([]);
    const [targetHub, setTargetHub] = useState('');

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.hubs'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? '',
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    const toggleJourney = (id) => {
        setSelectedJourneys((current) =>
            current.includes(id) ? current.filter((value) => value !== id) : [...current, id],
        );
    };

    const designate = () => {
        router.post(
            route('journeys.hubs.designate', targetHub),
            { journey_ids: selectedJourneys },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedJourneys([]);
                    setTargetHub('');
                },
            },
        );
    };

    const destroy = (hub) => {
        if (window.confirm(`Remove ${hub.name}? Journeys keep their hub name.`)) {
            router.delete(route('journeys.hubs.destroy', hub.id), { preserveScroll: true });
        }
    };

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Designation to Journey Hub" />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="min-w-0 flex-1 space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 className="text-lg font-semibold text-slate-900">
                                Designation to Journey Hub
                            </h1>
                            <p className="mt-0.5 text-xs text-slate-500">
                                Manage journey hubs and assign journeys to the hub that monitors them.
                            </p>
                        </div>
                        {canManage && (
                            <button
                                type="button"
                                onClick={() => {
                                    setEditing(null);
                                    setShowPanel(true);
                                }}
                                className="btn-primary min-h-9 px-3 text-xs"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Add Hub
                            </button>
                        )}
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Total Hubs"
                            value={stats.total ?? 0}
                            hint="Registered hubs"
                            icon={MapPin}
                            tone="brand"
                        />
                        <StatCard
                            label="Active"
                            value={stats.active ?? 0}
                            hint="Accepting journeys"
                            icon={CircleCheck}
                            tone="success"
                        />
                        <StatCard
                            label="Designated Journeys"
                            value={stats.designated ?? 0}
                            hint="Assigned to a hub"
                            icon={Share2}
                            tone="brand"
                        />
                        <StatCard
                            label="Awaiting Designation"
                            value={stats.undesignated ?? 0}
                            hint="No hub assigned"
                            icon={TriangleAlert}
                            tone="warning"
                        />
                    </div>

                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div className="min-w-0 flex-1 space-y-4">
                            <div className="card">
                                <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                                    <div className="relative min-w-[200px] flex-1">
                                        <input
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                            placeholder="Search hub name, code, or location..."
                                            className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                                        />
                                        <Search className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                    </div>
                                    <select
                                        className="input-field h-9 w-auto min-w-[110px] py-0 text-xs"
                                        value={filters.status || ''}
                                        onChange={(e) => applyFilters({ status: e.target.value, search })}
                                    >
                                        <option value="">All Hubs</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                {items.length === 0 ? (
                                    <div className="p-6">
                                        <EmptyState
                                            title="No journey hubs"
                                            description="Add a hub so journeys have a monitoring point to report into."
                                        />
                                    </div>
                                ) : (
                                    <div className="table-wrap">
                                        <table className="min-w-full text-xs">
                                            <thead>
                                                <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                                    <th className="px-4 py-2.5 font-medium">Hub</th>
                                                    <th className="px-4 py-2.5 font-medium">Location</th>
                                                    <th className="px-4 py-2.5 font-medium">Contact</th>
                                                    <th className="px-4 py-2.5 font-medium">Radius</th>
                                                    <th className="px-4 py-2.5 font-medium">Journeys</th>
                                                    <th className="px-4 py-2.5 font-medium">Status</th>
                                                    {canManage && (
                                                        <th className="px-4 py-2.5 font-medium">Actions</th>
                                                    )}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {items.map((hub) => (
                                                    <tr key={hub.id} className="align-middle hover:bg-slate-50/70">
                                                        <td className="px-4 py-2.5">
                                                            <p className="font-medium text-slate-800">{hub.name}</p>
                                                            <p className="text-[11px] text-slate-500">{hub.code}</p>
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            {hub.location || '—'}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            <p>{hub.contact_name || '—'}</p>
                                                            {hub.contact_phone && (
                                                                <p className="text-[11px] text-slate-500">
                                                                    {hub.contact_phone}
                                                                </p>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            {hub.radius_km} km
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            {formatNumber(hub.journeys_count ?? 0)}
                                                        </td>
                                                        <td className="px-4 py-2.5">
                                                            <span
                                                                className={cn(
                                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                                                                    hub.is_active
                                                                        ? 'bg-success-soft text-success'
                                                                        : 'bg-slate-100 text-slate-600',
                                                                )}
                                                            >
                                                                {hub.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                                        {canManage && (
                                                            <td className="px-4 py-2.5">
                                                                <div className="flex items-center gap-1.5">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setEditing(hub);
                                                                            setShowPanel(true);
                                                                        }}
                                                                        className="inline-flex items-center gap-1 rounded-md border border-brand/40 px-2 py-1 text-[11px] font-medium text-brand transition hover:bg-brand-soft"
                                                                    >
                                                                        <Pencil className="h-3 w-3" />
                                                                        Edit
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => destroy(hub)}
                                                                        className="inline-flex items-center gap-1 rounded-md border border-danger/40 px-2 py-1 text-[11px] font-medium text-danger transition hover:bg-danger-soft"
                                                                    >
                                                                        <Trash2 className="h-3 w-3" />
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}

                                {items.length > 0 && (
                                    <div className="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p className="text-[11px] text-slate-500">
                                            Showing {meta?.from ?? 0} to {meta?.to ?? items.length} of{' '}
                                            {formatNumber(meta?.total ?? items.length)} hubs
                                        </p>
                                        <div className="flex items-center gap-2">
                                            <span className="text-[11px] text-slate-500">Rows per page</span>
                                            <ListingPager
                                                links={pageLinks}
                                                perPage={meta?.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0]}
                                                onPerPageChange={(value) =>
                                                    applyFilters({ search, per_page: value })
                                                }
                                                onNavigate={(url) =>
                                                    url
                                                    && router.get(url, {}, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    })
                                                }
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="card">
                                <div className="border-b border-slate-100 px-4 py-3">
                                    <h2 className="text-sm font-semibold text-slate-900">
                                        Journeys Awaiting Designation
                                    </h2>
                                    <p className="mt-0.5 text-xs text-slate-500">
                                        Select journeys and assign them to the hub that will monitor them.
                                    </p>
                                </div>

                                {pending.length === 0 ? (
                                    <div className="p-6">
                                        <EmptyState
                                            title="Every journey has a hub"
                                            description="New journeys without a hub will appear here."
                                        />
                                    </div>
                                ) : (
                                    <>
                                        <ul className="divide-y divide-slate-100">
                                            {pending.map((journey) => (
                                                <li key={journey.id} className="flex items-center gap-3 px-4 py-2.5">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedJourneys.includes(journey.id)}
                                                        onChange={() => toggleJourney(journey.id)}
                                                        aria-label={`Select ${journey.code}`}
                                                        className="h-3.5 w-3.5 rounded border-slate-300 text-brand focus:ring-brand"
                                                    />
                                                    <span className="w-28 shrink-0 text-xs font-semibold text-brand">
                                                        {journey.code}
                                                    </span>
                                                    <span className="min-w-0 flex-1 truncate text-xs text-slate-700">
                                                        {journey.origin} → {journey.destination}
                                                    </span>
                                                    <span className="hidden shrink-0 text-[11px] text-slate-500 sm:block">
                                                        {journey.worker?.name || 'Unassigned'}
                                                    </span>
                                                    <span className="hidden shrink-0 text-[11px] text-slate-500 md:block">
                                                        {formatDateTime(journey.departure_at)}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>

                                        {canManage && (
                                            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3">
                                                <p className="text-[11px] text-slate-500">
                                                    {selectedJourneys.length} selected
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    <select
                                                        value={targetHub}
                                                        onChange={(e) => setTargetHub(e.target.value)}
                                                        aria-label="Target hub"
                                                        className="input-field h-9 w-auto min-w-[160px] py-0 text-xs"
                                                    >
                                                        <option value="">Select hub...</option>
                                                        {items
                                                            .filter((hub) => hub.is_active)
                                                            .map((hub) => (
                                                                <option key={hub.id} value={hub.id}>
                                                                    {hub.name}
                                                                </option>
                                                            ))}
                                                    </select>
                                                    <button
                                                        type="button"
                                                        onClick={designate}
                                                        disabled={!targetHub || selectedJourneys.length === 0}
                                                        className="btn-primary min-h-9 px-3 text-xs disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <Share2 className="h-3.5 w-3.5" />
                                                        Designate
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>

                        {canManage && showPanel && (
                            <HubFormPanel
                                hub={editing}
                                onCancel={() => setShowPanel(false)}
                                onSaved={() => {
                                    setShowPanel(false);
                                    setEditing(null);
                                }}
                            />
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
