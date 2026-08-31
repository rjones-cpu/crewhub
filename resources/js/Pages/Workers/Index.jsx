import { Head, router } from '@inertiajs/react';
import { Plus, UsersRound } from 'lucide-react';
import { useState } from 'react';
import AddWorkerDrawer from '@/Components/Workers/AddWorkerDrawer';
import WorkerFeaturesCard from '@/Components/Workers/WorkerFeaturesCard';
import WorkerFilters from '@/Components/Workers/WorkerFilters';
import WorkerStats from '@/Components/Workers/WorkerStats';
import WorkerTable from '@/Components/Workers/WorkerTable';
import Pagination from '@/Components/Shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { unwrapPaginated } from '@/utils/helpers';

export default function WorkersIndex({
    workers,
    stats = {},
    filters = {},
    filterOptions = {},
    projects = [],
    company = {},
    featureSummary = {},
    positions = [],
}) {
    const { items, links, meta } = unwrapPaginated(workers);
    const [drawerOpen, setDrawerOpen] = useState(false);

    return (
        <AppLayout
            title="Workers"
            titleIcon={UsersRound}
            subtitle="Manage, view and organize your workforce."
            showMeta={false}
            rightPanelOpen={drawerOpen}
            headerAction={(
                <button
                    type="button"
                    onClick={() => setDrawerOpen(true)}
                    className="inline-flex h-9 items-center gap-2 rounded-md bg-indigo-600 px-4 text-[10px] font-semibold text-white shadow-sm hover:bg-indigo-700"
                >
                    <Plus className="h-3.5 w-3.5" />
                    Add Worker
                </button>
            )}
        >
            <Head title="Workers" />

            <div className="space-y-3">
                <WorkerFeaturesCard summary={featureSummary} />

                <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <div className="space-y-2">
                        <WorkerFilters filters={filters} filterOptions={filterOptions} projects={projects} />
                        <WorkerStats stats={stats} />
                        <WorkerTable workers={items} />
                        <Pagination
                            links={links}
                            meta={meta}
                            compact
                            itemLabel="workers"
                            perPage={Number(filters.per_page || 5)}
                            perPageOptions={[5, 10, 25, 50]}
                            onPerPageChange={(perPage) => router.get(
                                route('workers.index'),
                                { ...filters, per_page: perPage },
                                { preserveState: true, preserveScroll: true, replace: true },
                            )}
                        />
                    </div>
                </section>
            </div>

            <AddWorkerDrawer
                open={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                projects={projects}
                company={company}
                positions={positions}
            />
        </AppLayout>
    );
}
