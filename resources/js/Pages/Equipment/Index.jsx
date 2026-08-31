import { Head } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';

export default function EquipmentIndex() {
    return (
        <AppLayout title="Equipment" subtitle="Plant and equipment register">
            <Head title="Equipment" />
            <Card>
                <EmptyState
                    title="Equipment register coming soon"
                    description="Plant allocation, servicing, and usage hours will appear here."
                />
            </Card>
        </AppLayout>
    );
}
