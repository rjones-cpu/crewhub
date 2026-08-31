import { Head } from '@inertiajs/react';
import CurrentProjectsPanel from '@/Components/MajorProjects/CurrentProjectsPanel';
import { majorProjectTabs } from '@/Components/MajorProjects/projectHelpers';
import AppLayout from '@/Layouts/AppLayout';

export default function MajorProjectsIndex({
    projects,
    filters,
    clients = [],
    canCreate = false,
    canAttemptCreate = false,
    canJoin = false,
    isSuperAdmin = false,
}) {
    return (
        <AppLayout
            title="Major Projects"
            subtitle="Manage all major projects you are currently part of."
            showMeta={false}
            tabs={majorProjectTabs({ canAttemptCreate, canJoin })}
            activeTab="major-projects.index"
            tabsVariant="boxed"
            rightPanelOpen={false}
        >
            <Head title="Major Projects" />
            <CurrentProjectsPanel
                projects={projects}
                filters={filters}
                clients={clients}
                canManage={canCreate || canAttemptCreate}
                canCreate={canCreate}
                canAttemptCreate={canAttemptCreate}
                isSuperAdmin={isSuperAdmin}
            />
        </AppLayout>
    );
}
