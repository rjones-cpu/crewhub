import { Head } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';

export default function DocumentsIndex() {
    return (
        <AppLayout title="Documents" subtitle="Workforce and project documentation">
            <Head title="Documents" />
            <Card>
                <EmptyState
                    title="Document library coming soon"
                    description="Certificates, inductions, and project documents will appear here."
                />
            </Card>
        </AppLayout>
    );
}
