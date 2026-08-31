import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BedDouble,
    Briefcase,
    Bus,
    ClipboardCheck,
    Clock3,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { useEffect } from 'react';
import CompanyScorecardCard from '@/Components/Dashboard/CompanyScorecardCard';
import CriticalAlertBanner from '@/Components/Dashboard/CriticalAlertBanner';
import KpiStrip from '@/Components/Dashboard/KpiStrip';
import ProjectPerformanceTable from '@/Components/Dashboard/ProjectPerformanceTable';
import ScorecardSummaryCard from '@/Components/Dashboard/ScorecardSummaryCard';
import TopPriorityActionsCard from '@/Components/Dashboard/TopPriorityActionsCard';
import UpcomingMobilizationsCard from '@/Components/Dashboard/UpcomingMobilizationsCard';
import WorkforceForecastChart from '@/Components/Dashboard/WorkforceForecastChart';
import AppLayout from '@/Layouts/AppLayout';
import { formatNumber, formatPercent } from '@/utils/formatters';

const REFRESH_MINUTES = 15;

export default function DashboardIndex({
    meta = {},
    kpis = {},
    forecast = [],
    scorecard = {},
    scorecardSummary = {},
    projectPerformance = [],
    mobilizations = [],
    priorityActions = [],
}) {
    useEffect(() => {
        const timer = setInterval(
            () => router.reload({ preserveScroll: true }),
            REFRESH_MINUTES * 60 * 1000,
        );

        return () => clearInterval(timer);
    }, []);

    const dateLabel = meta.range_start && meta.range_end
        ? `${meta.range_start} – ${meta.range_end}`
        : undefined;

    const kpiItems = [
        {
            label: 'Overall Company Rating',
            grade: kpis.company_grade,
            value: kpis.company_grade_label,
            hint: kpis.company_grade_status,
        },
        {
            label: 'Major Projects',
            value: formatNumber(kpis.major_projects),
            hint: 'Active',
            icon: Briefcase,
            tone: 'brand',
        },
        {
            label: 'Total Workers',
            value: formatNumber(kpis.total_workers),
            hint: 'Across all projects',
            icon: Users,
            tone: 'brand',
        },
        {
            label: 'Ready Workforce',
            value: formatPercent(kpis.ready_workforce_pct),
            hint: 'Ready to mobilize',
            icon: ShieldCheck,
            tone: 'success',
        },
        {
            label: 'Journeys Due Next 48h',
            value: formatNumber(kpis.journeys_due_48h),
            hint: 'workers due',
            icon: Bus,
            tone: 'journey',
        },
        {
            label: 'Accommodation Status',
            value: formatPercent(kpis.accommodation_confirmed_pct),
            hint: 'Reservations Confirmed',
            icon: BedDouble,
            tone: 'brand',
        },
        {
            label: 'Timesheets & Approvals',
            value: formatPercent(kpis.timesheets_approval_pct),
            hint: 'approved',
            icon: ClipboardCheck,
            tone: 'warning',
        },
        {
            label: 'Projects at Risk',
            value: formatNumber(kpis.projects_at_risk),
            hint: 'Needing attention',
            icon: AlertTriangle,
            tone: 'warning',
        },
    ];

    return (
        <AppLayout
            title="Crew Hub, Company Command"
            subtitle="Real-time command center for your company performance across all major projects."
            dateLabel={dateLabel}
            fitViewport
        >
            <Head title="Company Command" />

            <div className="flex h-full min-h-0 flex-col gap-3">
                <KpiStrip items={kpiItems} />

                <div className="grid min-h-0 flex-1 gap-3 lg:grid-cols-3">
                    <CompanyScorecardCard
                        scorecard={scorecard}
                        href={route('major-projects.index')}
                    />
                    <WorkforceForecastChart forecast={forecast} href={route('schedule.index')} />
                    <ScorecardSummaryCard
                        summary={scorecardSummary}
                        href={route('major-projects.index')}
                    />
                </div>

                <div className="grid min-h-0 flex-1 gap-3 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <ProjectPerformanceTable
                            projects={projectPerformance}
                            href={route('major-projects.index')}
                        />
                    </div>
                    <div className="lg:col-span-3">
                        <TopPriorityActionsCard
                            actions={priorityActions}
                            href={route('readiness.index')}
                        />
                    </div>
                    <div className="lg:col-span-4">
                        <UpcomingMobilizationsCard
                            mobilizations={mobilizations}
                            href={route('journeys.index')}
                        />
                    </div>
                </div>

                <CriticalAlertBanner
                    count={kpis.projects_at_risk}
                    href={route('major-projects.index')}
                />

                <div className="flex flex-wrap items-center gap-x-6 gap-y-1 px-1 text-[10px] text-slate-500">
                    {meta.timezone && (
                        <span className="inline-flex items-center gap-1.5">
                            <Clock3 className="h-3 w-3" />
                            All times shown in {meta.timezone}
                        </span>
                    )}
                    <span>Data auto-refreshes every {REFRESH_MINUTES} minutes</span>
                </div>
            </div>
        </AppLayout>
    );
}
