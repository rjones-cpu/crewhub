import { ChevronLeft, ChevronRight, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Modal from '@/Components/Shared/Modal';
import SearchInput from '@/Components/Shared/SearchInput';
import Select from '@/Components/Shared/Select';
import { formatNumber, statusLabel } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

const EMPTY_META = { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };

export default function WorkforceModal({ show, onClose, projectId }) {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [rows, setRows] = useState([]);
    const [statuses, setStatuses] = useState([]);
    const [meta, setMeta] = useState(EMPTY_META);
    const [loading, setLoading] = useState(false);

    // Reset paging whenever the filters change so results always start at page one.
    useEffect(() => {
        setPage(1);
    }, [search, status]);

    useEffect(() => {
        if (!show || !projectId) {
            return undefined;
        }

        const controller = new AbortController();
        const timer = setTimeout(() => {
            const params = new URLSearchParams({ project_id: projectId, page });

            if (search) params.set('search', search);
            if (status) params.set('status', status);

            setLoading(true);

            fetch(`${route('hierarchy.workforce')}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((payload) => {
                    setRows(payload.data || []);
                    setMeta(payload.meta || EMPTY_META);
                    setStatuses(payload.statuses || []);
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        setRows([]);
                    }
                })
                .finally(() => setLoading(false));
        }, search ? 300 : 0);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [show, projectId, search, status, page]);

    const pages = Array.from({ length: meta.last_page }, (_, index) => index + 1).filter(
        (candidate) =>
            candidate === 1 ||
            candidate === meta.last_page ||
            Math.abs(candidate - meta.current_page) <= 1,
    );

    return (
        <Modal show={show} onClose={onClose} maxWidth="6xl" title="Crew Hub Workforce">
            <p className="-mt-2 text-sm text-slate-500">
                {formatNumber(meta.total)} workers connected to this Crew Hub
            </p>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <div className="min-w-[220px] flex-1">
                    <SearchInput
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onClear={() => setSearch('')}
                        placeholder="Search worker name..."
                    />
                </div>
                <div className="w-full sm:w-48">
                    <Select
                        value={status}
                        placeholder="All statuses"
                        onChange={(event) => setStatus(event.target.value)}
                        options={statuses.map((option) => ({
                            value: option,
                            label: statusLabel(option),
                        }))}
                    />
                </div>
                <span className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand-soft px-3 text-sm font-medium text-brand">
                    <Users className="h-4 w-4" />
                    {formatNumber(meta.total)} Workers
                </span>
            </div>

            <div className="mt-4 max-h-[55vh] overflow-auto rounded-lg border border-slate-200">
                <table className="data-table">
                    <thead className="sticky top-0 z-10">
                        <tr>
                            <th>Worker Name</th>
                            <th>Position</th>
                            <th>Booking Code</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Reservation Status</th>
                            <th>Accommodation</th>
                            <th>Manager</th>
                        </tr>
                    </thead>
                    <tbody className={cn('divide-y divide-slate-100', loading && 'opacity-50')}>
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={8} className="py-8 text-center text-slate-500">
                                    {loading ? 'Loading workers…' : 'No workers match these filters.'}
                                </td>
                            </tr>
                        )}
                        {rows.map((worker) => (
                            <tr key={worker.id}>
                                <td>
                                    <span className="flex items-center gap-2">
                                        <Avatar name={worker.name} size="sm" />
                                        <span className="font-medium text-slate-900">
                                            {worker.name}
                                        </span>
                                    </span>
                                </td>
                                <td>{worker.position || '—'}</td>
                                <td>{worker.booking_code || '—'}</td>
                                <td>{worker.arrival || '—'}</td>
                                <td>{worker.departure || '—'}</td>
                                <td>
                                    {worker.reservation_status ? (
                                        <Badge status={worker.reservation_status} />
                                    ) : (
                                        '—'
                                    )}
                                </td>
                                <td>{worker.accommodation || '—'}</td>
                                <td>{worker.manager || '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-slate-500">
                    Showing {formatNumber(meta.from || 0)}–{formatNumber(meta.to || 0)} of{' '}
                    {formatNumber(meta.total)} workers
                </p>

                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        onClick={() => setPage((current) => Math.max(current - 1, 1))}
                        disabled={meta.current_page <= 1}
                        className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-40"
                        aria-label="Previous page"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>

                    {pages.map((candidate, index) => (
                        <span key={candidate} className="flex items-center">
                            {index > 0 && candidate - pages[index - 1] > 1 && (
                                <span className="px-1 text-slate-400">…</span>
                            )}
                            <button
                                type="button"
                                onClick={() => setPage(candidate)}
                                className={cn(
                                    'inline-flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-sm transition',
                                    candidate === meta.current_page
                                        ? 'border-brand bg-brand text-white'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                                )}
                            >
                                {candidate}
                            </button>
                        </span>
                    ))}

                    <button
                        type="button"
                        onClick={() => setPage((current) => Math.min(current + 1, meta.last_page))}
                        disabled={meta.current_page >= meta.last_page}
                        className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-40"
                        aria-label="Next page"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </Modal>
    );
}
