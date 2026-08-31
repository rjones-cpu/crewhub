import { Head, router } from '@inertiajs/react';
import { Clock, Search, ShieldAlert, ShieldCheck, ShieldX } from 'lucide-react';
import { useMemo, useState } from 'react';
import InsuranceDetailsPanel from '@/Components/Journeys/Insurance/InsuranceDetailsPanel';
import { CoverBadge, InsuranceStatusBadge } from '@/Components/Journeys/Insurance/insuranceHelpers';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from '@/Components/Journeys/ListingPager';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/utils/formatters';
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

export default function VehicleInsuranceIndex({
    vehicles,
    stats = {},
    filters = {},
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(vehicles);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];

    const [search, setSearch] = useState(filters.search || '');
    const [selectedId, setSelectedId] = useState(items[0]?.id ?? null);

    const selected = useMemo(
        () => items.find((item) => item.id === selectedId) || null,
        [items, selectedId],
    );

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.insurance'),
            {
                search: next.search ?? search,
                status: next.status ?? filters.status ?? '',
                cover: next.cover ?? filters.cover ?? '',
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Confirmation of Vehicle Insurance" />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0 flex-1 space-y-4">
                        <div>
                            <h1 className="text-lg font-semibold text-slate-900">
                                Confirmation of Vehicle Insurance
                            </h1>
                            <p className="mt-0.5 text-xs text-slate-500">
                                Verify that every vehicle carries valid cover before it is approved for a
                                journey.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <StatCard
                                label="Confirmed"
                                value={stats.confirmed ?? 0}
                                hint="Cover verified"
                                icon={ShieldCheck}
                                tone="success"
                            />
                            <StatCard
                                label="Awaiting Confirmation"
                                value={stats.awaiting ?? 0}
                                hint="Not yet checked"
                                icon={Clock}
                                tone="warning"
                            />
                            <StatCard
                                label="Expiring Soon"
                                value={stats.expiring ?? 0}
                                hint="Within 30 days"
                                icon={ShieldAlert}
                                tone="warning"
                            />
                            <StatCard
                                label="Expired"
                                value={stats.expired ?? 0}
                                hint="Blocks journey approval"
                                icon={ShieldX}
                                tone="danger"
                            />
                        </div>

                        <div className="card">
                            <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                                <div className="relative min-w-[200px] flex-1">
                                    <input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                        placeholder="Search vehicle, plate, provider, or policy..."
                                        className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                                    />
                                    <Search className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>
                                <select
                                    className="input-field h-9 w-auto min-w-[150px] py-0 text-xs"
                                    value={filters.status || ''}
                                    onChange={(e) => applyFilters({ status: e.target.value, search })}
                                >
                                    <option value="">All Confirmations</option>
                                    <option value="unverified">Awaiting Confirmation</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="flagged">Flagged</option>
                                </select>
                                <select
                                    className="input-field h-9 w-auto min-w-[130px] py-0 text-xs"
                                    value={filters.cover || ''}
                                    onChange={(e) => applyFilters({ cover: e.target.value, search })}
                                >
                                    <option value="">All Cover</option>
                                    <option value="valid">In cover</option>
                                    <option value="expiring">Expiring soon</option>
                                    <option value="expired">Expired</option>
                                    <option value="missing">No policy</option>
                                </select>
                            </div>

                            {items.length === 0 ? (
                                <div className="p-6">
                                    <EmptyState
                                        title="No vehicles to confirm"
                                        description="Register a vehicle with insurance details to start confirmations."
                                    />
                                </div>
                            ) : (
                                <div className="table-wrap">
                                    <table className="min-w-full text-xs">
                                        <thead>
                                            <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                                <th className="px-4 py-2.5 font-medium">Vehicle</th>
                                                <th className="px-4 py-2.5 font-medium">Provider</th>
                                                <th className="px-4 py-2.5 font-medium">Policy Number</th>
                                                <th className="px-4 py-2.5 font-medium">Valid Until</th>
                                                <th className="px-4 py-2.5 font-medium">Cover</th>
                                                <th className="px-4 py-2.5 font-medium">Confirmation</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {items.map((vehicle) => (
                                                <tr
                                                    key={vehicle.id}
                                                    onClick={() => setSelectedId(vehicle.id)}
                                                    className={cn(
                                                        'cursor-pointer align-middle transition hover:bg-slate-50/70',
                                                        selectedId === vehicle.id && 'bg-brand-soft/40',
                                                    )}
                                                >
                                                    <td className="px-4 py-2.5">
                                                        <p className="font-medium text-slate-800">
                                                            {vehicle.display_name}
                                                        </p>
                                                        <p className="text-[11px] text-slate-500">
                                                            {vehicle.license_plate}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-2.5 text-slate-600">
                                                        {vehicle.insurance_provider || '—'}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-slate-600">
                                                        {vehicle.policy_number || '—'}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-slate-600">
                                                        {formatDate(vehicle.policy_end_date)}
                                                    </td>
                                                    <td className="px-4 py-2.5">
                                                        <CoverBadge vehicle={vehicle} />
                                                    </td>
                                                    <td className="px-4 py-2.5">
                                                        <InsuranceStatusBadge vehicle={vehicle} />
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
                                        {formatNumber(meta?.total ?? items.length)} vehicles
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
                        <InsuranceDetailsPanel
                            vehicle={selected}
                            canManage={canManage}
                            onClose={() => setSelectedId(null)}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
