import { Head, router } from '@inertiajs/react';
import {
    Download,
    EllipsisVertical,
    Funnel,
    Plus,
    RefreshCw,
    Search,
    Shield,
    ShieldAlert,
    ShieldCheck,
    TriangleAlert,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from '@/Components/Journeys/ListingPager';
import NewAssessmentModal from '@/Components/Journeys/Risk/NewAssessmentModal';
import RiskDetailsPanel from '@/Components/Journeys/Risk/RiskDetailsPanel';
import { RiskPill, ScoreBar } from '@/Components/Journeys/Risk/riskHelpers';
import { JourneyStatusBadge } from '@/Components/Journeys/journeyHelpers';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { JOURNEY_STATUS_OPTIONS } from '@/utils/constants';
import { formatNumber } from '@/utils/formatters';
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

export default function CalculateRisk({
    assessments,
    stats = {},
    routes = [],
    journeys = [],
    filters = {},
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(assessments);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];

    const [search, setSearch] = useState(filters.search || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [selectedId, setSelectedId] = useState(items[0]?.id ?? null);
    const [createOpen, setCreateOpen] = useState(false);

    const selected = useMemo(
        () => items.find((item) => item.id === selectedId) || null,
        [items, selectedId],
    );

    const share = (value) => {
        const total = stats.total ?? 0;

        return total > 0 ? `${((value / total) * 100).toFixed(1)}% of total` : 'No assessments yet';
    };

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.risk'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? '',
                route: next.route ?? filters.route ?? '',
                from: next.from ?? from,
                to: next.to ?? to,
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    const exportUrl = route('journeys.risk.export', {
        search: filters.search || '',
        status: filters.status || '',
        route: filters.route || '',
        from: filters.from || '',
        to: filters.to || '',
    });

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Calculate Risk" />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0 flex-1 space-y-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h1 className="text-lg font-semibold text-slate-900">Calculate Risk</h1>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    Assess route, vehicle, and journey conditions to determine overall risk.
                                </p>
                            </div>
                            {canManage && (
                                <button
                                    type="button"
                                    onClick={() => setCreateOpen(true)}
                                    className="btn-primary min-h-9 px-3 text-xs"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    New Risk Assessment
                                </button>
                            )}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <StatCard
                                label="Total Assessments"
                                value={stats.total ?? 0}
                                hint="All time"
                                icon={Shield}
                                tone="brand"
                            />
                            <StatCard
                                label="Low Risk"
                                value={stats.low ?? 0}
                                hint={share(stats.low ?? 0)}
                                icon={ShieldCheck}
                                tone="success"
                            />
                            <StatCard
                                label="Medium Risk"
                                value={stats.medium ?? 0}
                                hint={share(stats.medium ?? 0)}
                                icon={ShieldAlert}
                                tone="warning"
                            />
                            <StatCard
                                label="High Risk"
                                value={stats.high ?? 0}
                                hint={share(stats.high ?? 0)}
                                icon={TriangleAlert}
                                tone="danger"
                            />
                        </div>

                        <div className="card">
                            <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                                <div className="relative min-w-[220px] flex-1">
                                    <input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                        placeholder="Search assessment ID, journey ID, driver or route..."
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
                                    className="input-field h-9 w-auto min-w-[130px] py-0 text-xs"
                                    value={filters.route || ''}
                                    onChange={(e) => applyFilters({ route: e.target.value, search })}
                                >
                                    <option value="">All Routes</option>
                                    {routes.map((option) => (
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
                                        title="No risk assessments"
                                        description="Run an assessment against a journey to score its risk."
                                        action={
                                            canManage ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setCreateOpen(true)}
                                                    className="btn-primary inline-flex"
                                                >
                                                    New Risk Assessment
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
                                                <th className="px-4 py-2.5 font-medium">Assessment ID</th>
                                                <th className="px-4 py-2.5 font-medium">Journey ID</th>
                                                <th className="px-4 py-2.5 font-medium">Driver / Worker</th>
                                                <th className="px-4 py-2.5 font-medium">Route</th>
                                                <th className="px-4 py-2.5 font-medium">Weather</th>
                                                <th className="px-4 py-2.5 font-medium">Road Conditions</th>
                                                <th className="px-4 py-2.5 font-medium">Vehicle Type</th>
                                                <th className="px-4 py-2.5 font-medium">Risk Score</th>
                                                <th className="px-4 py-2.5 font-medium">Risk Level</th>
                                                <th className="px-4 py-2.5 font-medium">Status</th>
                                                <th className="px-4 py-2.5 font-medium">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {items.map((assessment) => {
                                                const journey = assessment.journey || {};

                                                return (
                                                    <tr
                                                        key={assessment.id}
                                                        onClick={() => setSelectedId(assessment.id)}
                                                        className={cn(
                                                            'cursor-pointer align-middle transition hover:bg-slate-50/70',
                                                            selectedId === assessment.id && 'bg-brand-soft/40',
                                                        )}
                                                    >
                                                        <td className="px-4 py-2.5 font-semibold text-brand">
                                                            {assessment.code}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            {journey.code || '—'}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-700">
                                                            {journey.worker?.name || '—'}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            <p className="font-medium text-slate-800">
                                                                {journey.origin || '—'}
                                                            </p>
                                                            <p className="text-[11px] text-slate-500">
                                                                → {journey.destination || '—'}
                                                            </p>
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            <p>{assessment.weather || '—'}</p>
                                                            {assessment.temperature_c !== null && (
                                                                <p className="text-[11px] text-slate-500">
                                                                    {assessment.temperature_c}°C
                                                                </p>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            <p>{assessment.road_conditions || '—'}</p>
                                                            {assessment.road_condition_quality && (
                                                                <p className="text-[11px] text-slate-500">
                                                                    {assessment.road_condition_quality}
                                                                </p>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-600">
                                                            {journey.vehicle?.type_label
                                                                || journey.vehicle?.name
                                                                || '—'}
                                                        </td>
                                                        <td className="px-4 py-2.5">
                                                            <ScoreBar
                                                                score={assessment.score}
                                                                level={assessment.outcome}
                                                            />
                                                        </td>
                                                        <td className="px-4 py-2.5">
                                                            <RiskPill
                                                                level={assessment.outcome}
                                                                label={assessment.outcome_label}
                                                            />
                                                        </td>
                                                        <td className="px-4 py-2.5">
                                                            <JourneyStatusBadge
                                                                status={journey.status}
                                                                label={journey.status_label}
                                                            />
                                                        </td>
                                                        <td
                                                            className="px-4 py-2.5"
                                                            onClick={(event) => event.stopPropagation()}
                                                        >
                                                            <Dropdown>
                                                                <Dropdown.Trigger>
                                                                    <button
                                                                        type="button"
                                                                        aria-label={`Actions for ${assessment.code}`}
                                                                        className="inline-grid h-7 w-7 place-items-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                                    >
                                                                        <EllipsisVertical className="h-4 w-4" />
                                                                    </button>
                                                                </Dropdown.Trigger>
                                                                <Dropdown.Content
                                                                    width="48"
                                                                    contentClasses="bg-white py-1"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => setSelectedId(assessment.id)}
                                                                        className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                    >
                                                                        View details
                                                                    </button>
                                                                    {canManage && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() =>
                                                                                router.post(
                                                                                    route(
                                                                                        'journeys.risk.recalculate',
                                                                                        assessment.id,
                                                                                    ),
                                                                                    {},
                                                                                    { preserveScroll: true },
                                                                                )
                                                                            }
                                                                            className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                        >
                                                                            Recalculate risk
                                                                        </button>
                                                                    )}
                                                                </Dropdown.Content>
                                                            </Dropdown>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {items.length > 0 && (
                                <div className="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-[11px] text-slate-500">
                                        Showing {meta?.from ?? 0} to {meta?.to ?? items.length} of{' '}
                                        {formatNumber(meta?.total ?? items.length)} assessments
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
                    </div>

                    {selected && (
                        <RiskDetailsPanel
                            assessment={selected}
                            canManage={canManage}
                            onClose={() => setSelectedId(null)}
                        />
                    )}
                </div>
            </div>

            {canManage && (
                <NewAssessmentModal
                    show={createOpen}
                    onClose={() => setCreateOpen(false)}
                    journeys={journeys}
                />
            )}
        </AppLayout>
    );
}
