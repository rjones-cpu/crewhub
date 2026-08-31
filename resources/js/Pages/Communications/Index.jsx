import { Head } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';

export default function CommunicationsIndex() {
    return (
        <AppLayout title="Communications" subtitle="Messages and announcements">
            <Head title="Communications" />
            <Card>
                <EmptyState
                    title="Communications hub"
                    description="Team messaging and broadcasts will live here."
                />
            </Card>
        </AppLayout>
    );
}
