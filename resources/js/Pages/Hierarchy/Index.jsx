import { Head, Link, router, usePage } from '@inertiajs/react';
import { PencilLine, Plus } from 'lucide-react';
import { useState } from 'react';
import AccountabilityMatrix from '@/Components/Hierarchy/AccountabilityMatrix';
import AddManagerModal from '@/Components/Hierarchy/AddManagerModal';
import ApprovalPath from '@/Components/Hierarchy/ApprovalPath';
import AssignmentActivityCard from '@/Components/Hierarchy/AssignmentActivityCard';
import AssignmentPanel from '@/Components/Hierarchy/AssignmentPanel';
import ConnectionSummary from '@/Components/Hierarchy/ConnectionSummary';
import DelegationTable from '@/Components/Hierarchy/DelegationTable';
import HierarchyMap from '@/Components/Hierarchy/HierarchyMap';
import ProjectTabs from '@/Components/Hierarchy/ProjectTabs';
import WorkforceModal from '@/Components/Hierarchy/WorkforceModal';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';

const MANAGING_ROLES = ['super_admin', 'company_admin', 'workforce_manager'];

export default function HierarchyIndex({
    project,
    company = {},
    contact = null,
    managers = [],
    delegations = [],
    accountability = [],
    activity = [],
    approvalPath = [],
    availableManagers = [],
    workforceCount = 0,
}) {
    const { auth, majorProjects = [] } = usePage().props;
    const [addingManager, setAddingManager] = useState(false);
    const [viewingWorkforce, setViewingWorkforce] = useState(false);
    const canManage = MANAGING_ROLES.includes(auth?.user?.role);

    const removeManager = (manager) => {
        if (!window.confirm(`Disconnect ${manager.name} from ${project.name}?`)) {
            return;
        }

        router.delete(route('hierarchy.managers.destroy', manager.id), {
            preserveScroll: true,
        });
    };

    const toggleDelegation = (area, isDelegable) => {
        router.patch(
            route('hierarchy.delegations.update'),
            { major_project_id: project.id, area, is_delegable: isDelegable },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout
            title="Hierarchy Chart"
            subtitle="Select a major project to view and manage its reporting hierarchy."
        >
            <Head title="Hierarchy" />

            <div className="mb-4">
                <ProjectTabs activeProjectId={project?.id ?? null} />
            </div>

            {!project ? (
                <div className="card card-padding">
                    <EmptyState
                        title={majorProjects.length > 0 ? 'Select a major project' : 'No major project connected'}
                        description={
                            majorProjects.length > 0
                                ? 'Choose a project above to view its managers, delegations, workforce, and approval path.'
                                : 'Create or join a major project to build a reporting hierarchy.'
                        }
                    />
                </div>
            ) : (
                <div className="space-y-6">
                    <div className="flex flex-wrap justify-end gap-2">
                        {canManage && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setAddingManager(true)}
                                    className="btn-secondary min-h-10"
                                >
                                    <Plus className="h-4 w-4" />
                                    Add Manager
                                </button>
                                <Link
                                    href={route('major-projects.edit', project.id)}
                                    className="btn-primary min-h-10"
                                >
                                    <PencilLine className="h-4 w-4" />
                                    Edit Connection
                                </Link>
                            </>
                        )}
                    </div>

                    <ConnectionSummary project={project} company={company} />

                    <div className="grid gap-4 xl:grid-cols-2">
                        <HierarchyMap
                            project={project}
                            company={company}
                            contact={contact}
                            managers={managers}
                            workforceCount={workforceCount}
                            onAddManager={() => setAddingManager(true)}
                            onOpenWorkforce={() => setViewingWorkforce(true)}
                        />

                        <div className="space-y-4">
                            <AssignmentPanel
                                project={project}
                                company={company}
                                managers={managers}
                                onAddManager={() => setAddingManager(true)}
                                onRemoveManager={removeManager}
                            />
                            <DelegationTable
                                delegations={delegations}
                                canManage={canManage}
                                onToggle={toggleDelegation}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 xl:grid-cols-3">
                        <AccountabilityMatrix
                            rows={accountability}
                            areas={delegations.map((row) => row.area)}
                        />
                        <AssignmentActivityCard activity={activity} />
                        <ApprovalPath steps={approvalPath} />
                    </div>
                </div>
            )}

            <AddManagerModal
                show={addingManager}
                onClose={() => setAddingManager(false)}
                projectId={project?.id}
                candidates={availableManagers}
            />

            <WorkforceModal
                show={viewingWorkforce}
                onClose={() => setViewingWorkforce(false)}
                projectId={project?.id}
            />
        </AppLayout>
    );
}
