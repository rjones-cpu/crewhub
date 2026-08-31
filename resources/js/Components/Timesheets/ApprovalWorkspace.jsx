import { router } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/Shared/Button';
import Modal from '@/Components/Shared/Modal';
import ApprovalQueueTable from '@/Components/Timesheets/Queue/ApprovalQueueTable';
import QueueFilters from '@/Components/Timesheets/Queue/QueueFilters';
import QueueStatCard from '@/Components/Timesheets/Queue/QueueStatCard';
import SelectedTimesheetPanel from '@/Components/Timesheets/Queue/SelectedTimesheetPanel';
import StatusLegend from '@/Components/Timesheets/Queue/StatusLegend';

const RELOAD_OPTIONS = { preserveState: true, preserveScroll: true, replace: true };

const csvColumns = (includeClient) => [
    ['Worker Name', (row) => row.worker_name],
    ['Employee ID', (row) => row.employee_id],
    ['Company', (row) => row.company],
    ['Position / Trade', (row) => row.position],
    ['Week', (row) => row.week],
    ['Total Hours', (row) => row.total_hours],
    ['Worker Approval', (row) => row.worker_approval.state],
    ['Accommodation Confirmed', (row) => row.accommodation.state],
    ['Manager Approval', (row) => row.manager_approval.state],
    ...(includeClient ? [['Client Approval', (row) => row.client_approval.state]] : []),
    ['Current Stage', (row) => row.current_stage],
    ['Last Updated', (row) => row.last_updated],
];

function exportQueueCsv(rows = [], includeClient = false) {
    const columns = csvColumns(includeClient);
    const escape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    const csv = [
        columns.map(([header]) => escape(header)).join(','),
        ...rows.map((row) => columns.map(([, read]) => escape(read(row))).join(',')),
    ].join('\r\n');

    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `approval-queue-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

export default function ApprovalWorkspace({
    stats = [],
    queue = {},
    selected = null,
    filters = {},
    clientApprovalEnabled = false,
    listRoute = 'timesheets.index',
}) {
    const [returnPrompt, setReturnPrompt] = useState(null);

    const currentQuery = () => ({
        week: filters.week,
        search: filters.search,
        status: filters.status,
        approver_role: filters.approver_role,
        accommodation: filters.accommodation,
        per_page: filters.per_page,
        page: queue.meta?.current_page,
    });

    const applyFilters = (changes) => {
        router.get(
            route(listRoute),
            { ...currentQuery(), page: 1, ...changes },
            RELOAD_OPTIONS,
        );
    };

    const selectRow = (id) => {
        router.get(
            route(listRoute),
            { ...currentQuery(), selected: id },
            { ...RELOAD_OPTIONS, only: ['selected'] },
        );
    };

    const approve = () => {
        const action = selected.can.approve_client
            ? 'timesheets.approve-client'
            : 'timesheets.approve-manager';

        router.post(route(action, selected.id), {}, { preserveScroll: true });
    };

    const submitReturn = () => {
        router.post(
            route('timesheets.return', selected.id),
            { reason: returnPrompt.reason },
            {
                preserveScroll: true,
                onSuccess: () => setReturnPrompt(null),
            },
        );
    };

    return (
        <>
            <div className="space-y-3">
                <QueueFilters
                    filters={filters}
                    onFilter={applyFilters}
                    onRunCheck={() => router.post(route('timesheets.run-check'))}
                    onExport={() => exportQueueCsv(queue.rows, clientApprovalEnabled)}
                />

                <div className="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(220px,26%)]">
                    <div className="min-w-0 space-y-3">
                        <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            {stats.map((stat) => (
                                <QueueStatCard
                                    key={stat.key}
                                    {...stat}
                                    onView={() => applyFilters(stat.filter)}
                                />
                            ))}
                        </div>

                        <ApprovalQueueTable
                            rows={queue.rows}
                            meta={queue.meta}
                            selectedId={selected?.id}
                            perPage={filters.per_page}
                            perPageOptions={filters.options?.perPage}
                            showClientApproval={clientApprovalEnabled}
                            onSelect={selectRow}
                            onPageChange={(page) => router.get(
                                route(listRoute),
                                { ...currentQuery(), page },
                                RELOAD_OPTIONS,
                            )}
                            onPerPageChange={(perPage) => applyFilters({ per_page: perPage })}
                        />

                        <StatusLegend />
                    </div>

                    <div className="min-w-0 self-start">
                        <SelectedTimesheetPanel
                            timesheet={selected}
                            onApprove={approve}
                            onReturn={() => setReturnPrompt({ reason: '', title: 'Return for Correction' })}
                            onRequestChanges={() => setReturnPrompt({
                                reason: 'Changes requested: ',
                                title: 'Request Changes',
                            })}
                        />
                    </div>
                </div>
            </div>

            <Modal
                show={Boolean(returnPrompt)}
                onClose={() => setReturnPrompt(null)}
                title={returnPrompt?.title}
                maxWidth="md"
            >
                <label className="block">
                    <span className="mb-1 block text-sm font-medium text-slate-700">
                        Reason for the worker
                    </span>
                    <textarea
                        rows={4}
                        className="input-field"
                        value={returnPrompt?.reason ?? ''}
                        onChange={(event) => setReturnPrompt((current) => ({
                            ...current,
                            reason: event.target.value,
                        }))}
                    />
                </label>

                <div className="mt-4 flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => setReturnPrompt(null)}>
                        Cancel
                    </Button>
                    <Button onClick={submitReturn} disabled={!returnPrompt?.reason?.trim()}>
                        Send back to worker
                    </Button>
                </div>
            </Modal>
        </>
    );
}
