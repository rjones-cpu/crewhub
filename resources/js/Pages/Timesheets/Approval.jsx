import { Head } from '@inertiajs/react';
import ApprovalWorkspace from '@/Components/Timesheets/ApprovalWorkspace';
import AppLayout from '@/Layouts/AppLayout';
import { TIMESHEET_TABS } from '@/utils/constants';

export default function TimesheetApproval(props) {
    return (
        <AppLayout
            title="Timesheet Approval"
            tabs={TIMESHEET_TABS}
            activeTab="timesheets.approval"
            showMeta={false}
        >
            <Head title="Timesheet Approval" />
            <ApprovalWorkspace {...props} listRoute="timesheets.approval" />
        </AppLayout>
    );
}
