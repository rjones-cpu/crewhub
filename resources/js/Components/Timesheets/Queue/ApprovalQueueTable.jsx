import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import EmptyState from '@/Components/Shared/EmptyState';
import { cn } from '@/utils/helpers';
import ApprovalStateCell from './ApprovalState';

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

// The table is `table-fixed`, so these must total 100% for every column set or the
// browser redistributes the slack and clips cell contents.
const COLUMN_WIDTHS = {
    withClient: [11, 6, 8, 8, 8, 5, 8, 8, 8, 8, 7, 8, 7],
    withoutClient: [12, 7, 9, 9, 9, 6, 8, 9, 8, 8, 8, 7],
};

/** Compact page list: a sliding window of five pages, an ellipsis, then the last page. */
function pageNumbers(current, last) {
    if (last <= 6) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const start = Math.min(Math.max(1, current - 2), last - 5);
    const window = Array.from({ length: 5 }, (_, index) => start + index);

    return [...window, 'ellipsis', last];
}

export default function ApprovalQueueTable({
    rows = [],
    meta = {},
    selectedId,
    perPage,
    perPageOptions = [10, 25, 50],
    showClientApproval = false,
    onSelect,
    onPageChange,
    onPerPageChange,
}) {
    const currentPage = meta.current_page ?? 1;
    const lastPage = meta.last_page ?? 1;
    const columnWidths = showClientApproval
        ? COLUMN_WIDTHS.withClient
        : COLUMN_WIDTHS.withoutClient;

    return (
        <div className="card overflow-hidden rounded-lg">
            <div className="border-b border-slate-100 px-3 py-2.5">
                <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                    Approval Queue ({meta.total ?? 0})
                </h2>
            </div>

            {rows.length === 0 ? (
                <EmptyState
                    title="No timesheets in the queue"
                    description="Adjust the filters above, or run an approval check to pull the latest camp schedule."
                />
            ) : (
                <div className="table-wrap">
                    <table className="timesheet-queue-table">
                        <colgroup>
                            {columnWidths.map((width, index) => (
                                <col key={index} style={{ width: `${width}%` }} />
                            ))}
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Worker Name</th>
                                <th>Employee ID</th>
                                <th>Company</th>
                                <th>Position / Trade</th>
                                <th>Week</th>
                                <th>Total Hours</th>
                                <th>Worker Approval</th>
                                <th>Accommodation Confirmed</th>
                                <th>Manager Approval</th>
                                {showClientApproval && <th>Client Approval</th>}
                                <th>Current Stage</th>
                                <th>Last Updated</th>
                                <th className="whitespace-nowrap text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map((row) => {
                                const selected = row.id === selectedId;

                                return (
                                    <tr
                                        key={row.id}
                                        onClick={() => onSelect(row.id)}
                                        className={cn(
                                            'cursor-pointer align-top transition',
                                            selected
                                                ? 'bg-brand-soft/60 ring-1 ring-inset ring-brand/30'
                                                : 'hover:bg-slate-50',
                                        )}
                                    >
                                        <td>
                                            <span className="flex min-w-0 items-center gap-1.5">
                                                <Avatar
                                                    name={row.worker_name}
                                                    src={row.avatar}
                                                    size="sm"
                                                    className="h-6 w-6 text-[8px] ring-0"
                                                />
                                                <span className="min-w-0 truncate font-semibold text-slate-900">
                                                    {row.worker_name}
                                                </span>
                                            </span>
                                        </td>
                                        <td className="text-slate-500">{row.employee_id}</td>
                                        <td className="break-words text-slate-500">
                                            {row.company}
                                        </td>
                                        <td className="text-slate-500">{row.position}</td>
                                        <td className="break-words text-slate-500">
                                            {row.week}
                                        </td>
                                        <td className="font-medium text-slate-900">
                                            {row.total_hours}
                                        </td>
                                        <td>
                                            <ApprovalStateCell {...row.worker_approval} />
                                        </td>
                                        <td>
                                            <ApprovalStateCell {...row.accommodation} />
                                        </td>
                                        <td>
                                            <ApprovalStateCell {...row.manager_approval} />
                                        </td>
                                        {showClientApproval && (
                                            <td>
                                                <ApprovalStateCell {...row.client_approval} />
                                            </td>
                                        )}
                                        <td className="break-words text-slate-600">
                                            {row.current_stage}
                                        </td>
                                        <td className="text-slate-500">{row.last_updated}</td>
                                        <td>
                                            <Link
                                                href={route('timesheets.show', row.id)}
                                                onClick={(event) => event.stopPropagation()}
                                                className="ml-auto flex w-fit items-center gap-0.5 whitespace-nowrap rounded border border-slate-200 px-1 py-0.5 text-[8px] font-semibold text-brand transition hover:bg-brand-soft"
                                            >
                                                {row.actionable ? 'Review' : 'View'}
                                                <ChevronDown className="h-2.5 w-2.5" />
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-3 py-2">
                <div className="flex items-center gap-1">
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

                <p className="text-[9px] text-slate-500">
                    Showing {meta.from ?? 0} to {meta.to ?? 0} of {meta.total ?? 0} entries
                </p>

                <select
                    className="input-field !h-7 w-auto !rounded-md !py-0 !text-[9px]"
                    aria-label="Rows per page"
                    value={perPage}
                    onChange={(event) => onPerPageChange(Number(event.target.value))}
                >
                    {perPageOptions.map((option) => (
                        <option key={option} value={option}>
                            {option} per page
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}
