import { Head, router } from '@inertiajs/react';
import { Download, Info, ListFilter, Plus } from 'lucide-react';
import { useState } from 'react';
import ChangeRequestDetailPanel from '@/Components/Schedule/ChangeRequestDetailPanel';
import ChangeRequestsView from '@/Components/Schedule/ChangeRequestsView';
import ProjectFilterTabs from '@/Components/Schedule/ProjectFilterTabs';
import ScheduleBoard from '@/Components/Schedule/ScheduleBoard';
import ScheduleCalendarView from '@/Components/Schedule/ScheduleCalendarView';
import ScheduleContextRail from '@/Components/Schedule/ScheduleContextRail';
import ScheduleFilterBar from '@/Components/Schedule/ScheduleFilterBar';
import ScheduleKpiStrip from '@/Components/Schedule/ScheduleKpiStrip';
import ScheduleLegend from '@/Components/Schedule/ScheduleLegend';
import ScheduleListView from '@/Components/Schedule/ScheduleListView';
import ScheduleModificationsPanel from '@/Components/Schedule/ScheduleModificationsPanel';
import ScheduleNotice from '@/Components/Schedule/ScheduleNotice';
import ScheduleViewTabs from '@/Components/Schedule/ScheduleViewTabs';
import AppLayout from '@/Layouts/AppLayout';
import { DAY_FILL } from '@/Components/Schedule/scheduleConstants';
import { LIST_LEGEND } from '@/Components/Schedule/scheduleDesign';

const CALENDAR_SOURCES =
    'Schedule pulls data from Reservations, Position Forecast, Staffing Matrix, Shortages & Alerts, Worker Profiles & Readiness, Journey Management, Leave & Unavailability, and Special Requests.';

const REQUEST_SOURCES =
    'Change Requests are evaluated against worker schedules, leave & unavailability, reservations (arrivals/departures), staffing requirements, position requirements, Journey Management (travel status), shortages & alerts, special requests, and worker readiness.';

/** Inertia keeps empty values in the query string, so drop them before visiting. */
function cleanParams(params) {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
    );
}

/** Local Y-m-d: toISOString() would roll the date for anyone ahead of UTC. */
function isoDate(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function shiftDate(date, days) {
    const next = new Date(`${date}T00:00:00`);
    next.setDate(next.getDate() + days);

    return isoDate(next);
}

function downloadCsv(filename, rows) {
    const csv = rows
        .map((row) => row.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(','))
        .join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

export default function ScheduleIndex({
    projects = [],
    selectedProjectId = null,
    totalWorkerCount = 0,
    days = [],
    rows = [],
    totals = {},
    drafts = [],
    requests = [],
    canEdit = false,
    canAddProject = false,
    view = 'list',
    filters = {},
    filterOptions = {},
    timezoneLabel = '',
    kpis = [],
    listView = {},
    calendarView = {},
    changeRequests = {},
}) {
    const [showFilters, setShowFilters] = useState(true);
    const [railOpen, setRailOpen] = useState(true);

    const visit = (changes) => {
        router.get(route('schedule.index'), cleanParams({ ...filters, ...changes }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const stepWeek = (direction) => visit({ week: shiftDate(filters.week, direction * 7), page: 1 });

    const projectOptions = projects.map((project) => ({
        value: String(project.id),
        label: project.name,
    }));

    const filterSelect = (key, allLabel, options) => ({
        key,
        allLabel,
        options,
        value: filters[key] ?? 'all',
        onChange: (value) => visit({ [key]: value, page: 1 }),
    });

    const projectSelect = {
        key: 'project_id',
        allLabel: 'All Projects',
        options: projectOptions,
        value: filters.project_id ? String(filters.project_id) : 'all',
        onChange: (value) => visit({ project_id: value === 'all' ? null : value, page: 1 }),
    };

    const exportCurrentView = () => {
        if (view === 'requests') {
            downloadCsv('change-requests.csv', [
                ['Request ID', 'Worker', 'Department', 'Type', 'Requested Change', 'Reason', 'Impact', 'Status'],
                ...(changeRequests.rows || []).map((row) => [
                    row.id,
                    row.worker,
                    row.department,
                    row.type_label,
                    `${row.requested_change?.date ?? ''} ${row.requested_change?.detail ?? ''}`.trim(),
                    row.reason,
                    row.impact?.label,
                    row.status,
                ]),
            ]);

            return;
        }

        downloadCsv('schedule.csv', [
            ['Worker', 'Position', 'Department', 'Project', ...(listView.days || []).map((day) => day.label)],
            ...(listView.rows || []).map((row) => [
                row.name,
                row.position,
                row.department,
                row.project,
                ...(row.cells || []).map((cell) => [cell.label, cell.time].filter(Boolean).join(' ')),
            ]),
        ]);
    };

    const headerAction = (
        <div className="flex items-center gap-2">
            <button
                type="button"
                onClick={exportCurrentView}
                className="btn-secondary min-h-8 whitespace-nowrap px-2.5 py-1.5 text-xs"
            >
                <Download className="h-3.5 w-3.5" />
                Export
            </button>
            <button
                type="button"
                onClick={() => setShowFilters((open) => !open)}
                aria-pressed={showFilters}
                className="btn-secondary min-h-8 whitespace-nowrap px-2.5 py-1.5 text-xs"
            >
                <ListFilter className="h-3.5 w-3.5" />
                Filters
            </button>
            {view === 'requests' ? (
                <button
                    type="button"
                    disabled
                    title="Change request intake is not connected yet"
                    className="btn-primary min-h-8 whitespace-nowrap px-2.5 py-1.5 text-xs"
                >
                    <Plus className="h-3.5 w-3.5" />
                    Add Change Request
                </button>
            ) : (
                <button
                    type="button"
                    onClick={() => visit({ view: 'board' })}
                    title="Open the board, where shifts are added and painted"
                    className="btn-primary min-h-8 whitespace-nowrap px-2.5 py-1.5 text-xs"
                >
                    <Plus className="h-3.5 w-3.5" />
                    Add to Schedule
                </button>
            )}
        </div>
    );

    return (
        <AppLayout
            title="Schedule"
            subtitle="Manage worker schedules, shifts and assignments."
            subtitleMeta={timezoneLabel ? `All times shown in ${timezoneLabel}` : null}
            showMeta={false}
            headerAction={headerAction}
        >
            <Head title="Schedule" />

            <div className="space-y-3">
                {view !== 'requests' && <ScheduleKpiStrip items={kpis} onHintClick={() => visit({ view: 'calendar' })} />}

                {view === 'requests' && (
                    <div className="flex flex-col gap-2.5 xl:flex-row">
                        <div className="min-w-0 flex-1">
                            <ScheduleKpiStrip items={changeRequests.kpis || []} />
                        </div>
                        <ScheduleNotice
                            tone="brand"
                            title="Overtime approvals"
                            className="xl:w-[260px] xl:shrink-0"
                        >
                            Any overtime schedule is flagged and must be approved by the Lodge Manager before it can be
                            confirmed.
                        </ScheduleNotice>
                    </div>
                )}

                <ScheduleViewTabs active={view} onChange={(next) => visit({ view: next, page: 1 })} />

                {view === 'board' && (
                    <>
                        <ProjectFilterTabs
                            projects={projects}
                            selectedProjectId={selectedProjectId}
                            totalWorkerCount={totalWorkerCount}
                            params={{ view: 'board' }}
                            variant="camp"
                        />

                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="flex items-center gap-1.5 text-[11px] text-slate-500">
                                <Info className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                {canEdit
                                    ? 'Drag yellow Travel boxes to move or extend rotation boundaries. Drag blue Work boxes to paint a range. Right-click white selections to apply Work, Travel, or Off.'
                                    : 'Workers assigned to multiple projects appear in each assigned project tab.'}
                            </p>
                            <div className="flex items-center gap-2 text-[10px] font-medium uppercase tracking-wide text-slate-500">
                                <span className="flex items-center gap-1">
                                    <span
                                        className="h-2.5 w-2.5 rounded-sm"
                                        style={{ backgroundColor: DAY_FILL.work }}
                                    />
                                    Work
                                </span>
                                <span className="flex items-center gap-1">
                                    <span
                                        className="h-2.5 w-2.5 rounded-sm"
                                        style={{ backgroundColor: DAY_FILL.travel }}
                                    />
                                    Travel
                                </span>
                                <span className="flex items-center gap-1">
                                    <span className="h-2.5 w-2.5 rounded-sm border border-slate-300 bg-white" />
                                    Off
                                </span>
                            </div>
                        </div>

                        <ScheduleBoard days={days} rows={rows} totals={totals} canEdit={canEdit} />

                        <ScheduleModificationsPanel
                            drafts={drafts}
                            requests={requests}
                            selectedProjectId={selectedProjectId}
                            canEdit={canEdit}
                        />
                    </>
                )}

                {view === 'list' && (
                    <>
                        {showFilters && (
                            <ScheduleFilterBar
                                selects={[
                                    filterSelect('department', 'All Departments', filterOptions.departments || []),
                                    filterSelect('shift', 'All Shifts', filterOptions.shifts || []),
                                    projectSelect,
                                ]}
                                rangeLabel={listView.range_label}
                                onPrevious={() => stepWeek(-1)}
                                onNext={() => stepWeek(1)}
                                onToday={() => visit({ week: isoDate(new Date()), page: 1 })}
                            >
                                <ScheduleLegend items={LIST_LEGEND} />
                            </ScheduleFilterBar>
                        )}

                        <ScheduleListView list={listView} onPageChange={(page) => visit({ page })} />
                    </>
                )}

                {view === 'calendar' && (
                    <div className="flex flex-col gap-3 xl:flex-row">
                        <div className="min-w-0 flex-1 space-y-3">
                            {showFilters && (
                                <ScheduleFilterBar
                                    selects={[
                                        filterSelect('department', 'All Departments', filterOptions.departments || []),
                                        filterSelect('shift', 'All Shifts', filterOptions.shifts || []),
                                        projectSelect,
                                        filterSelect('status', 'All Worker Statuses', filterOptions.statuses || []),
                                    ]}
                                    stepper="labelled"
                                    rangeLabel={calendarView.range_label}
                                    rangeCaption="(2-Week View)"
                                    onPrevious={() => stepWeek(-1)}
                                    onNext={() => stepWeek(1)}
                                    onToday={() => visit({ week: isoDate(new Date()) })}
                                />
                            )}

                            <ScheduleCalendarView
                                calendar={calendarView}
                                railHidden={!railOpen}
                                onOpenRail={() => setRailOpen(true)}
                            />

                            <ScheduleNotice>{CALENDAR_SOURCES}</ScheduleNotice>
                        </div>

                        {railOpen && (
                            <div className="xl:w-[262px] xl:shrink-0">
                                <ScheduleContextRail
                                    rail={calendarView.rail || {}}
                                    onClose={() => setRailOpen(false)}
                                    onViewSpecialRequests={() => visit({ view: 'board' })}
                                    onViewAlerts={() => visit({ view: 'requests', page: 1 })}
                                />
                            </div>
                        )}
                    </div>
                )}

                {view === 'requests' && (
                    <>
                        <div className="flex flex-col gap-3 xl:flex-row">
                            <div className="min-w-0 flex-1">
                                <ChangeRequestsView
                                    requests={changeRequests}
                                    filterOptions={filterOptions}
                                    filters={filters}
                                    showFilters={showFilters}
                                    onFilterChange={(key, value) => {
                                        if (key === 'week') {
                                            stepWeek(value === 'prev' ? -1 : 1);

                                            return;
                                        }

                                        visit({ [key]: value, page: 1 });
                                    }}
                                    onSelect={(id) => visit({ request: id })}
                                    onPageChange={(page) => visit({ page })}
                                />
                            </div>

                            {changeRequests.selected && (
                                <div className="xl:w-[320px] xl:shrink-0">
                                    <ChangeRequestDetailPanel
                                        request={changeRequests.selected}
                                        canApprove={false}
                                        onClose={() => visit({ request: null })}
                                    />
                                </div>
                            )}
                        </div>

                        <ScheduleNotice>{REQUEST_SOURCES}</ScheduleNotice>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
