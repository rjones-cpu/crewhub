import { Head, Link, router } from '@inertiajs/react';
import {
    CircleAlert,
    CircleCheck,
    Plus,
    Search,
    ShieldAlert,
    Truck,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from '@/Components/Journeys/ListingPager';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';

const AVAILABILITY_CLASSES = {
    available: 'bg-success-soft text-success',
    in_use: 'bg-brand-soft text-brand',
    maintenance: 'bg-warning-soft text-warning',
    out_of_service: 'bg-danger-soft text-danger',
};

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

function InsuranceCell({ vehicle }) {
    if (!vehicle.policy_end_date) {
        return <span className="text-[11px] text-slate-400">Not captured</span>;
    }

    const tone = !vehicle.insurance_valid
        ? { className: 'text-danger', Icon: ShieldAlert, text: 'Expired' }
        : vehicle.insurance_expiring_soon
            ? { className: 'text-warning', Icon: CircleAlert, text: 'Expiring soon' }
            : { className: 'text-success', Icon: CircleCheck, text: 'Valid' };

    return (
        <div>
            <span className={cn('inline-flex items-center gap-1 font-medium', tone.className)}>
                <tone.Icon className="h-3.5 w-3.5" strokeWidth={1.8} />
                {tone.text}
            </span>
            <p className="mt-0.5 text-[11px] text-slate-500">
                Until {formatDate(vehicle.policy_end_date)}
            </p>
        </div>
    );
}

export default function VehiclesIndex({
    vehicles,
    stats = {},
    filters = {},
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(vehicles);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];
    const [search, setSearch] = useState(filters.search || '');

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.vehicles'),
            {
                search: next.search ?? search,
                type: next.type ?? filters.type ?? '',
                availability: next.availability ?? filters.availability ?? '',
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Registered Vehicles" />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="min-w-0 flex-1 space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Total Vehicles"
                            value={stats.total ?? 0}
                            hint="Registered fleet"
                            icon={Truck}
                            tone="brand"
                        />
                        <StatCard
                            label="Available"
                            value={stats.available ?? 0}
                            hint="Ready for journeys"
                            icon={CircleCheck}
                            tone="success"
                        />
                        <StatCard
                            label="In Maintenance"
                            value={stats.maintenance ?? 0}
                            hint="Currently unavailable"
                            icon={Wrench}
                            tone="warning"
                        />
                        <StatCard
                            label="Insurance Expiring"
                            value={stats.insurance_expiring ?? 0}
                            hint="Within 30 days"
                            icon={ShieldAlert}
                            tone="danger"
                        />
                    </div>

                    <div className="card">
                        <div className="flex flex-wrap items-start justify-between gap-3 px-4 pt-4">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900">Registered Vehicles</h2>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    Enter and manage vehicle details for journey approval.
                                </p>
                            </div>
                            {canManage && (
                                <Link
                                    href={route('journeys.vehicles.create')}
                                    className="btn-primary min-h-9 px-3 text-xs"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    Add Vehicle
                                </Link>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                            <div className="relative min-w-[220px] flex-1">
                                <input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                    placeholder="Search make, model, VIN, or plate..."
                                    className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                                />
                                <Search className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>
                            <select
                                className="input-field h-9 w-auto min-w-[130px] py-0 text-xs"
                                value={filters.availability || ''}
                                onChange={(e) => applyFilters({ availability: e.target.value, search })}
                            >
                                <option value="">All Availability</option>
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">In Maintenance</option>
                                <option value="out_of_service">Out of Service</option>
                            </select>
                        </div>

                        {items.length === 0 ? (
                            <div className="p-6">
                                <EmptyState
                                    title="No vehicles registered"
                                    description="Register a vehicle so it can be assigned to journeys."
                                    action={
                                        canManage ? (
                                            <Link
                                                href={route('journeys.vehicles.create')}
                                                className="btn-primary inline-flex"
                                            >
                                                Register Vehicle
                                            </Link>
                                        ) : null
                                    }
                                />
                            </div>
                        ) : (
                            <div className="table-wrap">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-y border-slate-100 bg-slate-50/70 text-left text-slate-500">
                                            <th className="px-4 py-2.5 font-medium">Vehicle</th>
                                            <th className="px-4 py-2.5 font-medium">Type</th>
                                            <th className="px-4 py-2.5 font-medium">License Plate</th>
                                            <th className="px-4 py-2.5 font-medium">Assigned Driver</th>
                                            <th className="px-4 py-2.5 font-medium">Base Location</th>
                                            <th className="px-4 py-2.5 font-medium">Insurance</th>
                                            <th className="px-4 py-2.5 font-medium">Availability</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {items.map((vehicle) => (
                                            <tr key={vehicle.id} className="align-middle hover:bg-slate-50/70">
                                                <td className="px-4 py-2.5">
                                                    <p className="font-medium text-slate-800">
                                                        {vehicle.display_name}
                                                    </p>
                                                    <p className="text-[11px] text-slate-500">
                                                        {vehicle.year} · VIN {vehicle.vin}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    {vehicle.vehicle_type_label}
                                                </td>
                                                <td className="px-4 py-2.5 font-medium text-slate-800">
                                                    {vehicle.license_plate}
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    {vehicle.assigned_driver?.name || '—'}
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-600">
                                                    {vehicle.base_location || '—'}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <InsuranceCell vehicle={vehicle} />
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <span
                                                        className={cn(
                                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                                                            AVAILABILITY_CLASSES[vehicle.availability]
                                                                || 'bg-slate-100 text-slate-600',
                                                        )}
                                                    >
                                                        {vehicle.availability_label}
                                                    </span>
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
            </div>
        </AppLayout>
    );
}
