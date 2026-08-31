import { Head, router } from '@inertiajs/react';
import { Info } from 'lucide-react';
import ApprovalStatusDonut from '@/Components/Timesheets/Reports/ApprovalStatusDonut';
import GeneratedReportsTable from '@/Components/Timesheets/Reports/GeneratedReportsTable';
import HoursByPositionChart from '@/Components/Timesheets/Reports/HoursByPositionChart';
import KeyExceptionsPanel from '@/Components/Timesheets/Reports/KeyExceptionsPanel';
import QuickExportsPanel from '@/Components/Timesheets/Reports/QuickExportsPanel';
import ReportFilters from '@/Components/Timesheets/Reports/ReportFilters';
import ReportKpiCard from '@/Components/Timesheets/Reports/ReportKpiCard';
import ReportLibraryPanel from '@/Components/Timesheets/Reports/ReportLibraryPanel';
import ScheduledReportsPanel from '@/Components/Timesheets/Reports/ScheduledReportsPanel';
import SubmissionApprovalTrendChart from '@/Components/Timesheets/Reports/SubmissionApprovalTrendChart';
import AppLayout from '@/Layouts/AppLayout';
import { TIMESHEET_TABS } from '@/utils/constants';

const RELOAD_OPTIONS = { preserveState: true, preserveScroll: true, replace: true };

export default function TimesheetReports({
    filters,
    stats = [],
    submissionTrend = [],
    hoursByPosition = [],
    approvalBreakdown = {},
    scheduledReports = [],
    reportLibrary = [],
    generatedReports = [],
    keyExceptions = [],
    quickExports = [],
    footnote = {},
}) {
    const queryFrom = (selection = {}) => ({
        week: selection.dateRange ?? filters.dateRange.selected,
        report_type: selection.reportType ?? filters.reportType.selected,
        status: selection.status ?? filters.status.selected,
        search: selection.search ?? '',
    });

    const apply = (selection) => {
        router.get(route('timesheets.reports'), queryFrom(selection), RELOAD_OPTIONS);
    };

    const download = (type, selection) => {
        const query = queryFrom(selection);
        window.location.href = route('timesheets.reports.export', {
            week: query.week,
            status: query.status,
            search: query.search,
            type: type && type !== 'all' ? type : query.report_type,
        });
    };

    return (
        <AppLayout title="Reports" tabs={TIMESHEET_TABS} activeTab="timesheets.reports" showMeta={false}>
            <Head title="Timesheet Reports" />

            <div className="space-y-4">
                <ReportFilters
                    filters={filters}
                    onGenerate={apply}
                    onExport={(selection) => download(selection.reportType, selection)}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {stats.map((stat) => (
                        <ReportKpiCard key={stat.key} {...stat} />
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-12">
                    <div className="xl:col-span-3">
                        <SubmissionApprovalTrendChart data={submissionTrend} />
                    </div>
                    <div className="xl:col-span-3">
                        <HoursByPositionChart data={hoursByPosition} />
                    </div>
                    <div className="xl:col-span-3">
                        <ApprovalStatusDonut breakdown={approvalBreakdown} />
                    </div>
                    <div className="xl:col-span-3">
                        <ScheduledReportsPanel schedules={scheduledReports} />
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-12">
                    <div className="xl:col-span-2">
                        <ReportLibraryPanel
                            reports={reportLibrary}
                            onSelect={(report) => download(report.id)}
                        />
                    </div>
                    <div className="xl:col-span-4">
                        <GeneratedReportsTable
                            reports={generatedReports}
                            onDownload={(report) => download(report.id)}
                        />
                    </div>
                    <div className="xl:col-span-3">
                        <KeyExceptionsPanel exceptions={keyExceptions} />
                    </div>
                    <div className="xl:col-span-3">
                        <QuickExportsPanel
                            templates={quickExports}
                            onExport={(item) => download(item.id)}
                        />
                    </div>
                </div>

                <p className="flex items-center gap-1.5 text-xs text-slate-500">
                    <Info className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                    All times and dates are shown in {footnote.timezone}. Data is updated as of{' '}
                    {footnote.updated_at}.
                </p>
            </div>
        </AppLayout>
    );
}
