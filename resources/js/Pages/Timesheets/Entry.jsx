import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Clock, PencilLine, Plus, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Button from '@/Components/Shared/Button';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { TIMESHEET_TABS } from '@/utils/constants';

const RELOAD_OPTIONS = { preserveState: true, preserveScroll: true, replace: true };

function Field({ label, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-[10px] font-semibold text-slate-500">{label}</span>
            {children}
        </label>
    );
}

export default function TimesheetEntry({
    stats = [],
    roster = {},
    filters = {},
    canCreate = false,
}) {
    const { majorProjects = [], currentProject } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const currentQuery = () => ({
        week: filters.week,
        search: filters.search,
        status: filters.status,
        per_page: filters.per_page,
        page: roster.meta?.current_page,
    });

    const applyFilters = (changes) => {
        router.get(
            route('timesheets.entry'),
            { ...currentQuery(), page: 1, ...changes },
            RELOAD_OPTIONS,
        );
    };

    useEffect(() => {
        if ((filters.search ?? '') === search) {
            return undefined;
        }

        const timer = setTimeout(() => applyFilters({ search }), 350);

        return () => clearTimeout(timer);
    }, [search]);

    const switchProject = (value) => {
        if (value === 'all') {
            router.post(route('major-projects.clear'), {}, { preserveState: true });

            return;
        }

        router.post(route('major-projects.switch', value), {}, { preserveState: true });
    };

    const startDraft = (workerId) => {
        router.post(route('timesheets.store'), {
            worker_id: workerId,
            week: filters.week,
        });
    };

    return (
        <AppLayout
            title="Timesheet"
            subtitle="Daily time capture for the current period"
            tabs={TIMESHEET_TABS}
            activeTab="timesheets.entry"
            showMeta={false}
        >
            <Head title="Timesheet" />

            <div className="space-y-3">
                <div className="card rounded-lg p-3">
                    <div className="flex flex-wrap items-end gap-2">
                        <Field label="Major Project" className="min-w-[135px] flex-1">
                            <select
                                className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                                value={currentProject?.id ?? 'all'}
                                onChange={(event) => switchProject(event.target.value)}
                            >
                                <option value="all">All Projects</option>
                                {majorProjects.map((project) => (
                                    <option key={project.id} value={project.id}>
                                        {project.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Week" className="min-w-[135px] flex-1">
                            <div className="relative">
                                <select
                                    className="input-field !h-8 appearance-none !rounded-md !py-0 !pr-8 !text-[11px]"
                                    value={filters.week}
                                    onChange={(event) => applyFilters({ week: event.target.value })}
                                >
                                    {(filters.options?.weeks ?? []).map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <CalendarDays className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>
                        </Field>

                        <div className="relative min-w-[180px] flex-[1.35] self-end">
                            <input
                                type="search"
                                className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                                placeholder="Search worker name or ID..."
                                aria-label="Search worker name or ID"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                            />
                        </div>

                        <Field label="Status" className="min-w-[115px] flex-1">
                            <select
                                className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                                value={filters.status}
                                onChange={(event) => applyFilters({ status: event.target.value })}
                            >
                                {(filters.options?.statuses ?? []).map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Button
                            variant="secondary"
                            className="!min-h-8 !rounded-md !px-2.5 !py-0 !text-[10px]"
                            onClick={() => router.post(route('timesheets.run-check'), { week: filters.week })}
                        >
                            <RefreshCw className="h-3.5 w-3.5 text-brand" />
                            Sync from Camp
                        </Button>
                    </div>
                </div>

                <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <button
                            key={stat.key}
                            type="button"
                            onClick={() => applyFilters(stat.filter)}
                            className="card rounded-lg p-3 text-left transition hover:border-brand/40"
                        >
                            <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                {stat.label}
                            </p>
                            <p className="mt-1 text-xl font-semibold text-slate-900">{stat.value}</p>
                        </button>
                    ))}
                </div>

                <div className="card overflow-hidden rounded-lg">
                    <div className="border-b border-slate-100 px-3 py-2.5">
                        <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                            Time capture roster ({roster.meta?.total ?? 0})
                        </h2>
                    </div>

                    {(roster.rows ?? []).length === 0 ? (
                        <EmptyState
                            title="No workers for this week"
                            description="Workers with timesheet access appear here. Create a draft to capture daily hours."
                        />
                    ) : (
                        <div className="table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Worker</th>
                                        <th>Position</th>
                                        <th>Week</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                        <th className="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {roster.rows.map((row) => (
                                        <tr key={row.worker_id}>
                                            <td>
                                                <div className="flex items-center gap-2">
                                                    <Avatar
                                                        name={row.name}
                                                        src={row.avatar}
                                                        size="sm"
                                                        className="h-7 w-7 text-[9px]"
                                                    />
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium text-slate-900">
                                                            {row.name}
                                                        </p>
                                                        <p className="truncate text-xs text-slate-500">
                                                            {row.employee_id} · {row.company}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="text-slate-500">{row.position}</td>
                                            <td className="text-slate-500">{row.week}</td>
                                            <td className="font-medium text-slate-900">{row.total_hours}</td>
                                            <td>
                                                <Badge status={row.status}>{row.status_label}</Badge>
                                            </td>
                                            <td>
                                                <div className="flex justify-end">
                                                    {row.timesheet_id ? (
                                                        <Link
                                                            href={route('timesheets.show', row.timesheet_id)}
                                                            className="btn-secondary !min-h-8 !px-2.5 !text-[11px]"
                                                        >
                                                            {row.can_edit ? (
                                                                <PencilLine className="h-3.5 w-3.5" />
                                                            ) : (
                                                                <Clock className="h-3.5 w-3.5" />
                                                            )}
                                                            {row.can_edit ? 'Enter hours' : 'View'}
                                                        </Link>
                                                    ) : canCreate ? (
                                                        <Button
                                                            className="!min-h-8 !px-2.5 !text-[11px]"
                                                            onClick={() => startDraft(row.worker_id)}
                                                        >
                                                            <Plus className="h-3.5 w-3.5" />
                                                            Start timesheet
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs text-slate-400">No sheet</span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="px-3 pb-3">
                        <Pagination
                            links={roster.links}
                            meta={roster.meta}
                            compact
                            itemLabel="workers"
                            perPage={filters.per_page}
                            perPageOptions={filters.options?.perPage}
                            onPerPageChange={(perPage) => applyFilters({ per_page: perPage })}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
