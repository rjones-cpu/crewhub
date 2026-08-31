import { Head } from '@inertiajs/react';
import ApprovalWorkspace from '@/Components/Timesheets/ApprovalWorkspace';
import AppLayout from '@/Layouts/AppLayout';
import { TIMESHEET_TABS } from '@/utils/constants';

export default function TimesheetsIndex(props) {
    return (
        <AppLayout title="Timesheets" tabs={TIMESHEET_TABS} activeTab="timesheets.index" showMeta={false}>
            <Head title="Timesheets" />
            <ApprovalWorkspace {...props} listRoute="timesheets.index" />
        </AppLayout>
    );
}
