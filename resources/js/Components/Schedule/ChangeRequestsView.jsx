import { CalendarDays, ChevronLeft, ChevronRight, MoreHorizontal, Search, SlidersHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import Select from '@/Components/Shared/Select';
import { cn } from '@/utils/helpers';
import { impactTone, requestStatus, requestType } from './scheduleDesign';

const PER_PAGE_OPTIONS = [10, 15, 25];

const compactSelectClass = '!h-8 !min-h-8 !rounded-md !py-0 !text-[11px]';

function withAll(options = [], label = 'All') {
    const list = Array.isArray(options) ? options : [];

    if (list.some((option) => option?.value === 'all')) {
        return list;
    }

    return [{ value: 'all', label }, ...list];
}

function pageNumbers(current, last) {
    if (last <= 6) {
        return Array.from({ length: Math.max(last, 1) }, (_, index) => index + 1);
    }

    const start = Math.min(Math.max(1, current - 2), last - 5);
    const window = Array.from({ length: 5 }, (_, index) => start + index);

    return [...window, 'ellipsis', last];
}

function FilterField({ label, htmlFor, className = '', children }) {
    return (
        <label htmlFor={htmlFor} className={cn('block min-w-[120px] flex-1', className)}>
            <span className="mb-1 block text-[9px] font-semibold uppercase tracking-wider text-slate-400">
                {label}
            </span>
            {children}
        </label>
    );
}

function PageButton({ label, active, disabled, onClick, children }) {
    return (
        <button
            type="button"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'inline-flex h-6 min-w-6 items-center justify-center rounded border px-1.5 text-[9px] font-medium transition',
                active
                    ? 'border-brand bg-brand text-white'
                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                disabled && 'cursor-not-allowed opacity-40',
            )}
        >
            {children ?? label}
        </button>
    );
}

export default function ChangeRequestsView({
    requests = {},
    filterOptions = {},
    filters = {},
    showFilters = true,
    onFilterChange = () => {},
    onSelect = () => {},
    onPageChange = () => {},
}) {
    const rows = Array.isArray(requests.rows) ? requests.rows : [];
    const pagination = requests.pagination || {};
    const selectedId = requests.selected?.id;
    const currentPage = pagination.current_page ?? 1;
    const lastPage = pagination.last_page ?? 1;
    const perPage = filters.per_page ?? pagination.per_page ?? 10;
    const [search, setSearch] = useState(filters.search || '');

    useEffect(() => {
        setSearch(filters.search || '');
    }, [filters.search]);

    const commitSearch = () => {
        const next = search.trim();

        if (next !== (filters.search || '')) {
            onFilterChange('search', next);
        }
    };

    return (
        <div className="card overflow-hidden rounded-xl">
            <div
                className={cn(
                    'flex-wrap items-end gap-2 border-b border-slate-100 px-3 py-2.5',
                    showFilters ? 'flex' : 'hidden',
                )}
            >
                <FilterField label="Request Type" htmlFor="cr-request-type">
                    <Select
                        id="cr-request-type"
                        options={withAll(filterOptions.requestTypes, 'All types')}
                        value={filters.request_type || 'all'}
                        onChange={(event) => onFilterChange('request_type', event.target.value)}
                        className={compactSelectClass}
                    />
                </FilterField>

                <FilterField label="Department" htmlFor="cr-department">
                    <Select
                        id="cr-department"
                        options={withAll(filterOptions.departments, 'All departments')}
                        value={filters.department || 'all'}
                        onChange={(event) => onFilterChange('department', event.target.value)}
                        className={compactSelectClass}
                    />
                </FilterField>

                <FilterField label="Shift" htmlFor="cr-shift">
                    <Select
                        id="cr-shift"
                        options={withAll(filterOptions.shifts, 'All shifts')}
                        value={filters.shift || 'all'}
                        onChange={(event) => onFilterChange('shift', event.target.value)}
                        className={compactSelectClass}
                    />
                </FilterField>

                <FilterField label="Status" htmlFor="cr-status">
                    <Select
                        id="cr-status"
                        options={withAll(filterOptions.requestStatuses, 'All statuses')}
                        value={filters.status || 'all'}
                        onChange={(event) => onFilterChange('status', event.target.value)}
                        className={compactSelectClass}
                    />
                </FilterField>

                <div className="min-w-[200px] flex-[1.2]">
                    <span className="mb-1 block text-[9px] font-semibold uppercase tracking-wider text-slate-400">
                        Date Range
                    </span>
                    <div className="flex h-8 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] text-slate-700">
                        <CalendarDays className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        <span className="min-w-0 flex-1 truncate">{requests.range_label || '—'}</span>
                        <button
                            type="button"
                            aria-label="Previous week"
                            onClick={() => onFilterChange('week', 'prev')}
                            className="inline-flex h-5 w-5 items-center justify-center rounded text-slate-400 transition hover:bg-slate-50 hover:text-slate-600"
                        >
                            <ChevronLeft className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            aria-label="Next week"
                            onClick={() => onFilterChange('week', 'next')}
                            className="inline-flex h-5 w-5 items-center justify-center rounded text-slate-400 transition hover:bg-slate-50 hover:text-slate-600"
                        >
                            <ChevronRight className="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div className="relative min-w-[180px] flex-[1.2]">
                    <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onBlur={commitSearch}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                commitSearch();
                            }
                        }}
                        placeholder="Search requests..."
                        aria-label="Search requests"
                        className="input-field h-8 min-h-8 rounded-md py-0 pl-8 pr-2 text-[11px]"
                    />
                </div>

                <button
                    type="button"
                    className="btn-secondary ml-auto h-8 min-h-8 gap-1.5 self-end px-2.5 py-0 text-[10px]"
                >
                    <SlidersHorizontal className="h-3.5 w-3.5" />
                    Columns
                </button>
            </div>

            <div className="table-wrap">
                <table className="data-table min-w-[1280px] text-[11px]">
                    <thead>
                        <tr>
                            <th className="!px-2.5 !py-2 text-[10px]">Request ID</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Worker</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Department / Position</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Request Type</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Current Shift</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Requested Change</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Date / Shift</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Reason</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Coverage Impact</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Status</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Submitted At</th>
                            <th className="!px-2.5 !py-2 text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={12} className="!whitespace-normal px-3 py-8 text-center text-[11px] text-slate-400">
                                    No change requests match the filters.
                                </td>
                            </tr>
                        ) : (
                            rows.map((row) => {
                                const selected = row.id === selectedId;
                                const type = requestType(row.type);
                                const TypeIcon = type.icon;
                                const status = requestStatus(row.status);
                                const impact = impactTone(row.impact?.value);

                                return (
                                    <tr
                                        key={row.id}
                                        onClick={() => onSelect(row.id)}
                                        className={cn(
                                            'cursor-pointer align-top transition',
                                            selected
                                                ? 'border-l-2 border-l-brand bg-brand-soft/60'
                                                : 'border-l-2 border-l-transparent hover:bg-slate-50',
                                        )}
                                    >
                                        <td className="!px-2.5 !py-2 text-[10px] font-medium text-slate-700">
                                            {row.id}
                                        </td>
                                        <td className="!px-2.5 !py-2">
                                            <span className="flex min-w-0 items-center gap-1.5">
                                                <Avatar
                                                    name={row.worker}
                                                    size="sm"
                                                    className="h-6 w-6 text-[8px] ring-0"
                                                />
                                                <span className="truncate text-[11px] font-medium text-slate-800">
                                                    {row.worker}
                                                </span>
                                            </span>
                                        </td>
                                        <td className="!whitespace-normal !px-2.5 !py-2">
                                            <p className="text-[11px] text-slate-700">{row.department}</p>
                                            <p className="text-[10px] text-slate-400">{row.position}</p>
                                        </td>
                                        <td className="!px-2.5 !py-2">
                                            <span
                                                className={cn(
                                                    'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-medium',
                                                    type.className,
                                                )}
                                            >
                                                <TypeIcon className="h-3 w-3 shrink-0" />
                                                {row.type_label || type.label}
                                            </span>
                                        </td>
                                        <td className="!whitespace-normal !px-2.5 !py-2">
                                            <p className="text-[11px] text-slate-700">{row.current_shift?.date}</p>
                                            <p className="text-[10px] text-slate-400">
                                                {row.current_shift?.shift}
                                                {row.current_shift?.time ? ` · ${row.current_shift.time}` : ''}
                                            </p>
                                        </td>
                                        <td className="!whitespace-normal !px-2.5 !py-2">
                                            <p className="text-[11px] font-medium text-slate-700">
                                                {row.requested_change?.date}
                                            </p>
                                            <p className="text-[10px] text-slate-600">{row.requested_change?.detail}</p>
                                            {row.requested_change?.note && (
                                                <p className="text-[10px] text-slate-400">{row.requested_change.note}</p>
                                            )}
                                        </td>
                                        <td className="!whitespace-normal !px-2.5 !py-2">
                                            <p className="text-[11px] text-slate-700">{row.date_shift?.date}</p>
                                            <p className="text-[10px] text-slate-400">{row.date_shift?.shift}</p>
                                        </td>
                                        <td className="!max-w-[160px] !whitespace-normal !px-2.5 !py-2">
                                            <p className="line-clamp-2 text-[10px] leading-snug text-slate-600">
                                                {row.reason}
                                            </p>
                                        </td>
                                        <td className="!px-2.5 !py-2">
                                            <span className={cn('inline-flex items-center gap-1.5 text-[10px] font-medium', impact.text)}>
                                                <span className={cn('h-1.5 w-1.5 rounded-full', impact.dot)} />
                                                {row.impact?.label || impact.label}
                                            </span>
                                        </td>
                                        <td className="!px-2.5 !py-2">
                                            <span className={cn('badge !px-1.5 !py-0.5 !text-[10px]', status.className)}>
                                                {status.label}
                                            </span>
                                        </td>
                                        <td className="!px-2.5 !py-2 text-[10px] text-slate-500">
                                            {row.submitted_at}
                                        </td>
                                        <td className="!px-2.5 !py-2">
                                            <button
                                                type="button"
                                                aria-label={`Open ${row.id}`}
                                                title="Open request"
                                                onClick={(event) => {
                                                    event.stopPropagation();
                                                    onSelect(row.id);
                                                }}
                                                className="inline-flex h-6 w-6 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                            >
                                                <MoreHorizontal className="h-3.5 w-3.5" />
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            <div className="grid grid-cols-1 items-center gap-2 border-t border-slate-100 px-3 py-2 sm:grid-cols-3">
                <p className="text-[10px] text-slate-500">
                    Showing {pagination.from ?? 0} to {pagination.to ?? 0} of {pagination.total ?? 0} requests
                </p>

                <div className="flex items-center justify-center gap-1">
                    <PageButton
                        label="Previous page"
                        disabled={currentPage <= 1}
                        onClick={() => onPageChange(currentPage - 1)}
                    >
                        <ChevronLeft className="h-3 w-3" />
                    </PageButton>

                    {pageNumbers(currentPage, lastPage).map((page, index) =>
                        page === 'ellipsis' ? (
                            <span key={`gap-${index}`} className="px-0.5 text-[9px] text-slate-400">
                                …
                            </span>
                        ) : (
                            <PageButton
                                key={page}
                                label={`Page ${page}`}
                                active={page === currentPage}
                                onClick={() => onPageChange(page)}
                            />
                        ),
                    )}

                    <PageButton
                        label="Next page"
                        disabled={currentPage >= lastPage}
                        onClick={() => onPageChange(currentPage + 1)}
                    >
                        <ChevronRight className="h-3 w-3" />
                    </PageButton>
                </div>

                <div className="flex justify-end">
                    <select
                        className="input-field !h-7 w-auto !rounded-md !py-0 !text-[9px]"
                        aria-label="Rows per page"
                        value={perPage}
                        onChange={(event) => onFilterChange('per_page', Number(event.target.value))}
                    >
                        {PER_PAGE_OPTIONS.map((option) => (
                            <option key={option} value={option}>
                                {option} / page
                            </option>
                        ))}
                    </select>
                </div>
            </div>
        </div>
    );
}
