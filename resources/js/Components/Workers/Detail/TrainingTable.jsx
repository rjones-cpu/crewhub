import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, FileText, MoreVertical, Search } from 'lucide-react';
import { useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import Badge from '@/Components/Shared/Badge';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate, statusLabel } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

const SCOPES = [
    { key: 'all', label: 'All Training' },
    { key: 'required', label: 'Required' },
    { key: 'elective', label: 'Elective' },
];

function expiryTone(record) {
    if (record.is_expired) {
        return 'text-danger';
    }

    if (record.is_expiring_soon) {
        return 'text-warning';
    }

    return record.expires_at ? 'text-success' : 'text-slate-400';
}

export default function TrainingTable({
    worker,
    records = [],
    counts = {},
    statuses = [],
    filters = {},
    page = 1,
    perPage = 8,
    total = 0,
    onPreview,
    selectedCertificateId,
}) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilters = (overrides) => {
        router.get(
            route('workers.show', worker.id),
            {
                tab: 'training',
                scope: overrides.scope ?? filters.scope ?? 'all',
                training_status: overrides.status ?? filters.status ?? '',
                training_search: overrides.search ?? search,
                training_page: overrides.page ?? 1,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const lastPage = Math.max(Math.ceil(total / perPage), 1);
    const firstRow = total === 0 ? 0 : (page - 1) * perPage + 1;
    const lastRow = Math.min(page * perPage, total);

    return (
        <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2 p-3">
                <div className="flex flex-wrap items-center gap-1.5">
                    {SCOPES.map((scope) => {
                        const active = (filters.scope || 'all') === scope.key;

                        return (
                            <button
                                key={scope.key}
                                type="button"
                                onClick={() => applyFilters({ scope: scope.key })}
                                className={cn(
                                    'rounded-md px-2.5 py-1 text-[10px] font-semibold transition',
                                    active
                                        ? 'bg-brand-soft text-brand'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700',
                                )}
                            >
                                {scope.label} ({counts[scope.key] ?? 0})
                            </button>
                        );
                    })}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <select
                        value={filters.status || ''}
                        onChange={(event) => applyFilters({ status: event.target.value })}
                        className="h-7 rounded-md border-slate-200 bg-white pl-2 pr-7 text-[10px] text-slate-600 focus:border-brand focus:ring-brand"
                    >
                        <option value="">All Status</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {statusLabel(status)}
                            </option>
                        ))}
                    </select>

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters({ search });
                        }}
                        className="relative"
                    >
                        <Search className="pointer-events-none absolute left-2 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search training..."
                            className="h-7 w-44 rounded-md border-slate-200 pl-7 text-[10px] placeholder:text-slate-400 focus:border-brand focus:ring-brand"
                        />
                    </form>
                </div>
            </div>

            {records.length === 0 ? (
                <div className="border-t border-slate-100 p-3">
                    <EmptyState
                        title="No training records"
                        description="Training assigned to this worker will appear here once it is added."
                    />
                </div>
            ) : (
                <>
                    <div className="table-wrap border-t border-slate-100">
                        <table className="w-full table-fixed text-left text-[9px]">
                            <colgroup>
                                <col className="w-[22%]" />
                                <col className="w-[13%]" />
                                <col className="w-[12%]" />
                                <col className="w-[14%]" />
                                <col className="w-[13%]" />
                                <col className="w-[18%]" />
                                <col className="w-[8%]" />
                            </colgroup>
                            <thead>
                                <tr className="bg-slate-50 text-slate-600">
                                    <th className="px-3 py-2 font-semibold">Training</th>
                                    <th className="px-3 py-2 font-semibold">Category</th>
                                    <th className="px-3 py-2 font-semibold">Status</th>
                                    <th className="px-3 py-2 font-semibold">Completed / Issued</th>
                                    <th className="px-3 py-2 font-semibold">Expiry Date</th>
                                    <th className="px-3 py-2 font-semibold">Certificate</th>
                                    <th className="px-3 py-2 text-center font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {records.map((record) => {
                                    const certificate = record.certificate;
                                    const selected = certificate && certificate.id === selectedCertificateId;

                                    return (
                                        <tr key={record.id} className={cn('hover:bg-slate-50', selected && 'bg-brand-soft/40')}>
                                            <td className="truncate px-3 py-2 font-medium text-slate-900">
                                                {record.course_name}
                                            </td>
                                            <td className="truncate px-3 py-2 text-slate-600">
                                                {record.category || '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Badge status={record.status} className="px-1.5 py-0.5 text-[8px]" />
                                            </td>
                                            <td className="px-3 py-2 text-slate-600">
                                                {record.completed_at ? formatDate(record.completed_at) : '—'}
                                            </td>
                                            <td className={cn('px-3 py-2 font-medium', expiryTone(record))}>
                                                {record.expires_at ? formatDate(record.expires_at) : '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {certificate ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => onPreview?.(record)}
                                                        className="inline-flex items-center gap-1 font-medium text-brand hover:underline"
                                                    >
                                                        <FileText className="h-3 w-3 text-danger" />
                                                        View Certificate
                                                    </button>
                                                ) : (
                                                    <span className="text-slate-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-center">
                                                <Dropdown>
                                                    <Dropdown.Trigger>
                                                        <button
                                                            type="button"
                                                            className="inline-grid h-6 w-6 place-items-center rounded text-slate-500 hover:bg-slate-100"
                                                            aria-label={`Actions for ${record.course_name}`}
                                                        >
                                                            <MoreVertical className="h-3.5 w-3.5" />
                                                        </button>
                                                    </Dropdown.Trigger>
                                                    <Dropdown.Content width="48" contentClasses="bg-white py-1">
                                                        <button
                                                            type="button"
                                                            onClick={() => onPreview?.(record)}
                                                            disabled={!certificate}
                                                            className="block w-full px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50 disabled:text-slate-300"
                                                        >
                                                            View certificate
                                                        </button>
                                                        {certificate?.file_url && (
                                                            <a
                                                                href={certificate.file_url}
                                                                download
                                                                className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                                                            >
                                                                Download certificate
                                                            </a>
                                                        )}
                                                        {certificate && (
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    if (window.confirm(`Remove the certificate for ${record.course_name}?`)) {
                                                                        router.delete(
                                                                            route('workers.certificates.destroy', [worker.id, certificate.id]),
                                                                            { preserveScroll: true },
                                                                        );
                                                                    }
                                                                }}
                                                                className="block w-full px-3 py-1.5 text-left text-[9px] text-rose-600 hover:bg-rose-50"
                                                            >
                                                                Remove certificate
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

                    <div className="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-[9px] text-slate-500">
                        <span>
                            Showing {firstRow} to {lastRow} of {total} trainings
                        </span>

                        <div className="flex items-center gap-1">
                            <button
                                type="button"
                                disabled={page <= 1}
                                onClick={() => applyFilters({ page: page - 1 })}
                                className="grid h-6 w-6 place-items-center rounded border border-slate-200 text-slate-500 disabled:opacity-40"
                                aria-label="Previous page"
                            >
                                <ChevronLeft className="h-3 w-3" />
                            </button>

                            {Array.from({ length: lastPage }, (_, index) => index + 1).map((number) => (
                                <button
                                    key={number}
                                    type="button"
                                    onClick={() => applyFilters({ page: number })}
                                    className={cn(
                                        'h-6 min-w-6 rounded border px-1.5 font-semibold',
                                        number === page
                                            ? 'border-brand bg-brand text-white'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                                    )}
                                >
                                    {number}
                                </button>
                            ))}

                            <button
                                type="button"
                                disabled={page >= lastPage}
                                onClick={() => applyFilters({ page: page + 1 })}
                                className="grid h-6 w-6 place-items-center rounded border border-slate-200 text-slate-500 disabled:opacity-40"
                                aria-label="Next page"
                            >
                                <ChevronRight className="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </>
            )}
        </section>
    );
}
