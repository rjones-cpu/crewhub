import { Link, router } from '@inertiajs/react';
import {
    CalendarDays,
    Download,
    EllipsisVertical,
    Eye,
    Funnel,
    Navigation,
    Plus,
    RefreshCw,
    Route,
    Search,
    TriangleAlert,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import EmptyState from '@/Components/Shared/EmptyState';
import { JOURNEY_RISK_OPTIONS, JOURNEY_STATUS_OPTIONS } from '@/utils/constants';
import { formatDateTime, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';
import JourneyDetailsPanel from './JourneyDetailsPanel';
import JourneySubnav from './JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from './ListingPager';
import NewJourneyModal from './NewJourneyModal';
import { JourneyStatusBadge, RiskMeter } from './journeyHelpers';

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

export default function JourneyListPanel({
    journeys,
    stats = {},
    filters = {},
    workers = [],
    canCreate = false,
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(journeys);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];
    const total = meta?.total ?? items.length;

    const [search, setSearch] = useState(filters.search || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [selectedId, setSelectedId] = useState(items[0]?.id ?? null);
    const [createOpen, setCreateOpen] = useState(false);
    const [processingId, setProcessingId] = useState(null);

    const selected = useMemo(
        () => items.find((item) => item.id === selectedId) || null,
        [items, selectedId],
    );

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.index'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? '',
                risk: next.risk ?? filters.risk ?? '',
                from: next.from ?? from,
                to: next.to ?? to,
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    const exportUrl = route('journeys.export', {
        search: filters.search || '',
        status: filters.status || '',
        risk: filters.risk || '',
        from: filters.from || '',
        to: filters.to || '',
    });

    const updateStatus = (journey, status) => {
        setProcessingId(journey.id);
        router.patch(
            route('journeys.status', journey.id),
            { status },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
            <JourneySubnav />

            <div className="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start">
                <div className="min-w-0 flex-1 space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Total Journeys"
                            value={stats.total ?? 0}
                            hint="All time"
                            icon={Route}
                            tone="brand"
                        />
                        <StatCard
                            label="Planned"
                            value={stats.planned ?? 0}
                            hint="Upcoming journeys"
                            icon={CalendarDays}
                            tone="success"
                        />
                        <StatCard
                            label="En Route"
                            value={stats.en_route ?? 0}
                            hint="Currently in progress"
                            icon={Navigation}
                            tone="warning"
                        />
                        <StatCard
                            label="High Risk"
                            value={stats.high_risk ?? 0}
                            hint="Needs attention"
                            icon={TriangleAlert}
                            tone="danger"
                        />
                    </div>

                    <div className="card">
                        <div className="flex flex-wrap items-start justify-between gap-3 px-4 pt-4">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900">All Journey List</h2>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    View, search, and manage all journey records.
                                </p>
                            </div>
                            {canCreate && (
                                <button
                                    type="button"
                                    onClick={() => setCreateOpen(true)}
                                    className="btn-primary min-h-9 px-3 text-xs"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    New Journey
                                </button>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                            <div className="relative min-w-[220px] flex-1">
                                <input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            applyFilters({ search });
                                        }
                                    }}
                                    placeholder="Search journey ID / driver / route..."
                                    className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                                />
                                <Search className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>
                            <select
                                className="input-field h-9 w-auto min-w-[120px] py-0 text-xs"
                                value={filters.status || ''}
                                onChange={(e) => applyFilters({ status: e.target.value, search })}
                            >
                                <option value="">All Statuses</option>
                                {JOURNEY_STATUS_OPTIONS.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="input-field h-9 w-auto min-w-[120px] py-0 text-xs"
                                value={filters.risk || ''}
                                onChange={(e) => applyFilters({ risk: e.target.value, search })}
                            >
                                <option value="">All Risk Levels</option>
                                {JOURNEY_RISK_OPTIONS.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <input
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                                aria-label="From date"
                                className="input-field h-9 w-auto py-0 text-xs"
                            />
                            <input
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                                aria-label="To date"
                                className="input-field h-9 w-auto py-0 text-xs"
                            />
                            <button
                                type="button"
                                onClick={() => applyFilters({ search, from, to })}
                                className="btn-secondary min-h-9 px-3 text-xs"
                            >
                                <Funnel className="h-3.5 w-3.5" />
                                Filter
                            </button>
                            <a href={exportUrl} className="btn-secondary min-h-9 px-3 text-xs">
                                <Download className="h-3.5 w-3.5" />
                                Export
                            </a>
                            <button
                                type="button"
                                onClick={() => router.reload()}
                                className="btn-secondary min-h-9 px-3 text-xs"
                            >
                                <RefreshCw className="h-3.5 w-3.5" />
                                Refresh
                            </button>
                        </div>

                        {items.length === 0 ? (
                            <div className="p-6">
                                <EmptyState
                                    title="No journeys"
                                    description="Create a journey or adjust filters to see records."
                                    action={
                                        canCreate ? (
                                            <button
                                                type="button"
                                                onClick={() => setCreateOpen(true)}
                                                className="btn-primary inline-flex"
                                            >
                                                New Journey
                                            </button>
                                        ) : null
                                    }
                                />
                            </div>
                        ) : (
                            <div className="table-wrap">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                            <th className="px-4 py-2.5 font-medium">Journey ID</th>
                                            <th className="px-4 py-2.5 font-medium">Driver / Worker</th>
                                            <th className="px-4 py-2.5 font-medium">Vehicle</th>
                                            <th className="px-4 py-2.5 font-medium">Route</th>
                                            <th className="whitespace-nowrap px-4 py-2.5 font-medium">Departure</th>
                                            <th className="whitespace-nowrap px-4 py-2.5 font-medium">ETA</th>
                                            <th className="px-4 py-2.5 font-medium">Risk Score</th>
                                            <th className="px-4 py-2.5 font-medium">Status</th>
                                            <th className="px-4 py-2.5 font-medium">Journey Hub</th>
                                            <th className="px-4 py-2.5 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {items.map((journey) => (
                                            <tr
                                                key={journey.id}
                                                className={cn(
                                                    'cursor-pointer align-middle transition hover:bg-slate-50/70',
                                                    selectedId === journey.id && 'bg-brand-soft/40',
                                                )}
                                                onClick={() => setSelectedId(journey.id)}
                                            >
                                                <td className="px-4 py-2.5">
                                                    <button
                                                        type="button"
                                                        className="font-semibold text-brand hover:underline"
                                                        onClick={() => setSelectedId(journey.id)}
                                                    >
                                                        {journey.code}
                                                    </button>
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-700">
                                                    {journey.worker?.name || '—'}
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    <p className="font-medium text-slate-800">
                                                        {journey.vehicle_plate || '—'}
                                                    </p>
                                                    <p className="text-[11px] text-slate-500">
                                                        {journey.vehicle_model || ''}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    {journey.origin} → {journey.destination}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                                    {formatDateTime(journey.departure_at)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                                    {formatDateTime(journey.arrival_at)}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <RiskMeter
                                                        level={journey.risk_level}
                                                        segments={journey.risk_segments}
                                                        label={journey.risk_label}
                                                    />
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <JourneyStatusBadge
                                                        status={journey.status}
                                                        label={journey.status_label}
                                                    />
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    {journey.hub || '—'}
                                                </td>
                                                <td
                                                    className="px-4 py-2.5"
                                                    onClick={(event) => event.stopPropagation()}
                                                >
                                                    <div className="flex items-center gap-1">
                                                        <button
                                                            type="button"
                                                            onClick={() => setSelectedId(journey.id)}
                                                            className="inline-grid h-7 w-7 place-items-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                            aria-label={`View ${journey.code}`}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </button>
                                                        <Dropdown>
                                                            <Dropdown.Trigger>
                                                                <button
                                                                    type="button"
                                                                    className="inline-grid h-7 w-7 place-items-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                                    aria-label={`Actions for ${journey.code}`}
                                                                >
                                                                    <EllipsisVertical className="h-4 w-4" />
                                                                </button>
                                                            </Dropdown.Trigger>
                                                            <Dropdown.Content width="48" contentClasses="bg-white py-1">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setSelectedId(journey.id)}
                                                                    className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                >
                                                                    View details
                                                                </button>
                                                                <Link
                                                                    href={route('journeys.show', journey.id)}
                                                                    className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                >
                                                                    View full details
                                                                </Link>
                                                                {canManage && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => updateStatus(journey, 'in_transit')}
                                                                        className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                    >
                                                                        Mark en route
                                                                    </button>
                                                                )}
                                                            </Dropdown.Content>
                                                        </Dropdown>
                                                    </div>
                                                </td>
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
                                    {formatNumber(total)} journeys
                                </p>
                                <div className="flex items-center gap-2">
                                    <span className="text-[11px] text-slate-500">Rows per page</span>
                                    <ListingPager
                                        links={pageLinks}
                                        perPage={meta?.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0]}
                                        onPerPageChange={(value) => applyFilters({ search, per_page: value })}
                                        onNavigate={(url) =>
                                            url && router.get(url, {}, { preserveState: true, preserveScroll: true })
                                        }
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {selected && (
                    <JourneyDetailsPanel
                        journey={selected}
                        canManage={canManage}
                        processing={processingId === selected.id}
                        onClose={() => setSelectedId(null)}
                        onStatusChange={(status) => updateStatus(selected, status)}
                    />
                )}
            </div>

            {canCreate && (
                <NewJourneyModal
                    show={createOpen}
                    onClose={() => setCreateOpen(false)}
                    workers={workers}
                />
            )}
        </div>
    );
}
