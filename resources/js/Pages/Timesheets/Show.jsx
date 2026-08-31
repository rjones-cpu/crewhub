import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { useMemo, useState } from 'react';
import Button from '@/Components/Shared/Button';
import ApprovalRecordPanel from '@/Components/Timesheets/Detail/ApprovalRecordPanel';
import ApprovalSettingsPanel from '@/Components/Timesheets/Detail/ApprovalSettingsPanel';
import EquipmentUsagePanel from '@/Components/Timesheets/Detail/EquipmentUsagePanel';
import TimeEntryTable from '@/Components/Timesheets/Detail/TimeEntryTable';
import TimesheetRequirements from '@/Components/Timesheets/Detail/TimesheetRequirements';
import TimesheetWorkerCard from '@/Components/Timesheets/Detail/TimesheetWorkerCard';
import WeeklySummaryCard from '@/Components/Timesheets/Detail/WeeklySummaryCard';
import WorkerNotesCard from '@/Components/Timesheets/Detail/WorkerNotesCard';
import PrintableTimesheet from '@/Components/Timesheets/PrintableTimesheet';
import AppLayout from '@/Layouts/AppLayout';
import { TIMESHEET_TABS } from '@/utils/constants';

function summarize(dayEntries = [], equipmentEntries = []) {
    const pick = (key) => dayEntries.reduce((sum, row) => sum + Number(row[key] || 0), 0);

    return {
        regular_hours: pick('regular_hours'),
        overtime_hours: pick('overtime_hours'),
        double_time_hours: pick('double_time_hours'),
        travel_hours: pick('travel_hours'),
        standby_hours: pick('standby_hours'),
        break_hours: pick('break_hours'),
        hours: pick('total_hours'),
        equipment_hours: equipmentEntries.reduce((sum, row) => sum + Number(row.hours || 0), 0),
    };
}

export default function TimesheetsShow({
    timesheet: initial,
    approvalRecord = [],
    clientApprovalEnabled = false,
    can = {},
}) {
    const [form, setForm] = useState({
        ...initial,
        day_entries: initial.day_entries || [],
        equipment_entries: initial.equipment_entries || [],
        compliance: initial.compliance || {},
    });
    const [processing, setProcessing] = useState(false);

    const editable = Boolean(can.update && form.editable);
    const totals = useMemo(
        () => summarize(form.day_entries, form.equipment_entries),
        [form.day_entries, form.equipment_entries],
    );

    const patchForm = (partial) => setForm((current) => ({ ...current, ...partial }));

    const payload = () => ({
        day_entries: form.day_entries,
        equipment_entries: form.equipment_entries,
        compliance: {
            ...form.compliance,
            requirements: form.requirements,
            approval_settings: form.approval_settings,
        },
        worker_comment: form.worker_comment,
        manager_comment: form.manager_comment,
        client_comment: form.client_comment,
        worker_signature: form.worker_signature,
        client_approval_required: form.approval_settings?.client,
        supervisor_name: form.supervisor_name,
    });

    const run = (method, url, data = {}) => {
        setProcessing(true);
        router[method](url, data, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const saveDraft = () => run('put', route('timesheets.update', form.id), payload());

    const submitTimesheet = () => {
        const data = payload();

        run('post', route('timesheets.submit', form.id), {
            ...data,
            compliance: {
                ...data.compliance,
                signature: Boolean(form.worker_signature || form.compliance?.signature),
                worker_declaration: true,
            },
        });
    };

    const updateApprovalSetting = (key, value) => {
        patchForm({
            approval_settings: { ...form.approval_settings, [key]: value },
            // Client approval has a real column; keep it in sync for the printable copy.
            ...(key === 'client' ? { client_approval_required: value } : {}),
        });
    };

    return (
        <AppLayout
            title="Timesheet"
            tabs={TIMESHEET_TABS}
            activeTab="timesheets.entry"
            showMeta={false}
        >
            <Head title={`Timesheet — ${form.worker?.full_name || form.id}`} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <Link
                    href={route('timesheets.index')}
                    className="btn-secondary min-h-10"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Timesheets
                </Link>

                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="secondary" onClick={() => window.print()}>
                        <Printer className="h-4 w-4" />
                        Print
                    </Button>
                    {editable && (
                        <>
                            <Button variant="secondary" disabled={processing} onClick={saveDraft}>
                                Save Draft
                            </Button>
                            <Button disabled={processing} onClick={submitTimesheet}>
                                Submit for Approval
                            </Button>
                        </>
                    )}
                    {can.approve_manager && (
                        <Button
                            disabled={processing}
                            onClick={() => run('post', route('timesheets.approve-manager', form.id), {
                                comment: form.manager_comment,
                            })}
                        >
                            Approve (Manager)
                        </Button>
                    )}
                    {can.approve_client && (
                        <Button
                            disabled={processing}
                            onClick={() => run('post', route('timesheets.approve-client', form.id), {
                                comment: form.client_comment,
                            })}
                        >
                            Approve (Client)
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-4 xl:grid-cols-12">
                <div className="space-y-4 xl:col-span-8">
                    <TimesheetWorkerCard timesheet={form} />

                    <TimesheetRequirements
                        requirements={form.requirements}
                        editable={editable}
                        onChange={(requirements) => patchForm({ requirements })}
                    />

                    <TimeEntryTable
                        entries={form.day_entries}
                        editable={editable}
                        showHourlyRate={Boolean(form.requirements?.hourly_rate)}
                        onChange={(day_entries) => patchForm({ day_entries })}
                    />

                    {form.requirements?.equipment && (
                        <EquipmentUsagePanel
                            entries={form.equipment_entries}
                            editable={editable}
                            onChange={(equipment_entries) => patchForm({ equipment_entries })}
                        />
                    )}

                    <div className="grid gap-4 lg:grid-cols-5">
                        <div className="lg:col-span-2">
                            <WeeklySummaryCard totals={totals} />
                        </div>
                        <div className="lg:col-span-3">
                            <WorkerNotesCard
                                notes={form.worker_comment}
                                editable={editable}
                                onChange={(worker_comment) => patchForm({ worker_comment })}
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-4 xl:col-span-4">
                    <ApprovalSettingsPanel
                        settings={form.approval_settings}
                        editable={editable}
                        showClient={clientApprovalEnabled}
                        onChange={updateApprovalSetting}
                    />
                    <ApprovalRecordPanel record={approvalRecord} />
                </div>
            </div>

            <PrintableTimesheet timesheet={form} totals={totals} />
        </AppLayout>
    );
}
