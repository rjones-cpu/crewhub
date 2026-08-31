import { ChevronLeft, ChevronRight } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import { cn } from '@/utils/helpers';
import { shiftStatus } from './scheduleDesign';

/** Statuses that read as a single centred word rather than a shift and its hours. */
const SINGLE_LINE = ['booked_off', 'unavailable', 'off'];

/** Page numbers around the current page, with gaps collapsed to an ellipsis. */
function pageWindow(current, last) {
    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages = new Set([1, 2, last, current - 1, current, current + 1]);
    const sorted = [...pages].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);
    const windowed = [];

    sorted.forEach((page, index) => {
        if (index > 0 && page - sorted[index - 1] > 1) {
            windowed.push('gap');
        }

        windowed.push(page);
    });

    return windowed;
}

function DayCell({ cell }) {
    const status = shiftStatus(cell.status);
    const centred = SINGLE_LINE.includes(cell.status);

    return (
        <td className="border-b border-l border-slate-100 p-1 align-middle">
            <div
                className={cn(
                    'flex h-9 flex-col justify-center rounded-md px-2',
                    status.cell,
                    centred && 'items-center',
                )}
            >
                <span className={cn('text-[10px] font-semibold leading-tight', status.text)}>{cell.label}</span>
                {cell.time && <span className="text-[10px] leading-tight text-slate-400">{cell.time}</span>}
            </div>
        </td>
    );
}

export default function ScheduleListView({ list = {}, onPageChange = () => {} }) {
    const days = list.days || [];
    const rows = list.rows || [];
    const pagination = list.pagination || { from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };

    return (
        <div className="card overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1100px] border-collapse">
                    <thead>
                        <tr className="border-b border-slate-200">
                            <th className="sticky left-0 z-10 w-[190px] bg-white px-3 py-2 text-left text-[10px] font-semibold text-slate-500">
                                Worker / Position
                            </th>
                            <th className="w-[110px] px-3 py-2 text-left text-[10px] font-semibold text-slate-500">
                                Department
                            </th>
                            <th className="w-[110px] px-3 py-2 text-left text-[10px] font-semibold text-slate-500">
                                Project
                            </th>
                            {days.map((day) => (
                                <th
                                    key={day.date}
                                    className={cn(
                                        'border-l border-slate-100 px-3 py-2 text-left text-[10px] font-semibold',
                                        day.is_today ? 'text-brand' : 'text-slate-500',
                                    )}
                                >
                                    {day.label}
                                </th>
                            ))}
                        </tr>
                    </thead>

                    <tbody>
                        {rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={days.length + 3}
                                    className="px-3 py-10 text-center text-xs text-slate-500"
                                >
                                    No workers match the selected filters.
                                </td>
                            </tr>
                        )}

                        {rows.map((row) => (
                            <tr key={row.id} className="group">
                                <td className="sticky left-0 z-10 border-b border-slate-100 bg-white px-3 py-1.5 group-hover:bg-slate-50">
                                    <div className="flex items-center gap-2">
                                        <Avatar name={row.name} size="xs" className="shrink-0" />
                                        <div className="min-w-0">
                                            <p className="truncate text-[11px] font-semibold text-slate-800">
                                                {row.name}
                                            </p>
                                            <p className="truncate text-[10px] text-slate-400">{row.position}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="border-b border-slate-100 px-3 py-1.5 text-[10px] text-slate-600">
                                    {row.department}
                                </td>
                                <td className="border-b border-slate-100 px-3 py-1.5 text-[10px] text-slate-600">
                                    {row.project}
                                </td>
                                {(row.cells || []).map((cell) => (
                                    <DayCell key={cell.date} cell={cell} />
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-3 py-2">
                <p className="text-[10px] text-slate-500">
                    Showing {pagination.from} to {pagination.to} of {pagination.total} workers
                </p>

                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        disabled={pagination.current_page <= 1}
                        onClick={() => onPageChange(pagination.current_page - 1)}
                        aria-label="Previous page"
                        className="inline-flex h-6 w-6 items-center justify-center rounded border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-40"
                    >
                        <ChevronLeft className="h-3.5 w-3.5" />
                    </button>

                    {pageWindow(pagination.current_page, pagination.last_page).map((page, index) =>
                        page === 'gap' ? (
                            <span key={`gap-${index}`} className="px-1 text-[10px] text-slate-400">
                                …
                            </span>
                        ) : (
                            <button
                                key={page}
                                type="button"
                                onClick={() => onPageChange(page)}
                                className={cn(
                                    'inline-flex h-6 min-w-6 items-center justify-center rounded px-1.5 text-[10px] font-medium transition',
                                    page === pagination.current_page
                                        ? 'bg-brand text-white'
                                        : 'border border-slate-200 text-slate-600 hover:bg-slate-50',
                                )}
                            >
                                {page}
                            </button>
                        ),
                    )}

                    <button
                        type="button"
                        disabled={pagination.current_page >= pagination.last_page}
                        onClick={() => onPageChange(pagination.current_page + 1)}
                        aria-label="Next page"
                        className="inline-flex h-6 w-6 items-center justify-center rounded border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-40"
                    >
                        <ChevronRight className="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>
    );
}
