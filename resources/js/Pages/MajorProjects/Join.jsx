import { Head } from '@inertiajs/react';
import JoinInvitationsPanel from '@/Components/MajorProjects/JoinInvitationsPanel';
import { majorProjectTabs } from '@/Components/MajorProjects/projectHelpers';
import AppLayout from '@/Layouts/AppLayout';

export default function MajorProjectsJoin({
    invitations,
    filters,
    companies = [],
    canCreate = false,
    canAttemptCreate = false,
    canJoin = true,
}) {
    return (
        <AppLayout
            title="Major Projects"
            subtitle="Review invitations from project owners and choose whether to join."
            showMeta={false}
            tabs={majorProjectTabs({ canAttemptCreate, canJoin })}
            activeTab="major-projects.join"
            tabsVariant="boxed"
        >
            <Head title="Join a Project" />
            <JoinInvitationsPanel
                invitations={invitations}
                filters={filters}
                companies={companies}
            />
        </AppLayout>
    );
}
