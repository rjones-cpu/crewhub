import { Head, router } from '@inertiajs/react';
import { Play, ShieldCheck } from 'lucide-react';
import MajorProjectsStrip from '@/Components/Dashboard/MajorProjectsStrip';
import CriticalConcerns from '@/Components/Readiness/CriticalConcerns';
import ReadinessByCategory from '@/Components/Readiness/ReadinessByCategory';
import ReadinessDonutChart from '@/Components/Readiness/ReadinessDonutChart';
import ReadinessStats from '@/Components/Readiness/ReadinessStats';
import RecentReadinessActivity from '@/Components/Readiness/RecentReadinessActivity';
import UpcomingExpiries from '@/Components/Readiness/UpcomingExpiries';
import WorkersRequiringAttention from '@/Components/Readiness/WorkersRequiringAttention';
import Button from '@/Components/Shared/Button';
import AppLayout from '@/Layouts/AppLayout';

export default function ReadinessIndex({
    stats = {},
    overview = [],
    categories = [],
    attention = [],
    criticalConcerns = [],
    upcomingExpiries = [],
    recentActivity = [],
    meta = {},
}) {
    const runCheck = () => {
        router.post(route('readiness.run-check'));
    };

    return (
        <AppLayout
            title="Readiness"
            titleIcon={ShieldCheck}
            subtitle="Monitor workforce readiness, compliance, and mobilization status."
            dateLabel={meta.period_label}
        >
            <Head title="Readiness" />

            <div className="space-y-2.5">
                <div className="flex justify-end">
                    <Button
                        className="!min-h-8 !rounded-md !px-3 !py-0 !text-[9px]"
                        onClick={runCheck}
                    >
                        <Play className="h-3.5 w-3.5" />
                        Run Readiness Check
                    </Button>
                </div>

                <MajorProjectsStrip compact showAdd={false} showAll={false} />

                <ReadinessStats stats={stats} />

                <div className="grid gap-2.5 xl:grid-cols-12">
                    <div className="space-y-2.5 xl:col-span-9">
                        <div className="grid gap-2.5 lg:grid-cols-[0.9fr_1.1fr]">
                            <ReadinessDonutChart
                                overview={overview}
                                total={stats.total ?? 0}
                                dataAsOf={meta.generated_at}
                            />
                            <ReadinessByCategory categories={categories} />
                        </div>
                        <WorkersRequiringAttention attention={attention} />
                    </div>

                    <div className="space-y-2.5 xl:col-span-3">
                        <CriticalConcerns concerns={criticalConcerns} />
                        <UpcomingExpiries expiries={upcomingExpiries} />
                        <RecentReadinessActivity activities={recentActivity} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
