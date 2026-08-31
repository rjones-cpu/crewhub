import { Head } from '@inertiajs/react';
import JourneyListPanel from '@/Components/Journeys/JourneyListPanel';
import AppLayout from '@/Layouts/AppLayout';

export default function JourneysIndex({
    journeys,
    stats = {},
    filters = {},
    workers = [],
    canCreate = false,
    canManage = false,
}) {
    return (
        <AppLayout
            title="Journey Management"
            subtitle="View, search, and manage all journey records."
            showMeta={false}
        >
            <Head title="Journey Management" />
            <JourneyListPanel
                journeys={journeys}
                stats={stats}
                filters={filters}
                workers={workers}
                canCreate={canCreate}
                canManage={canManage}
            />
        </AppLayout>
    );
}
