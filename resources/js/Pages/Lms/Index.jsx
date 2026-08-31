import { Head } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';

export default function LmsIndex() {
    return (
        <AppLayout title="LMS" subtitle="Training courses and competency records">
            <Head title="LMS" />
            <Card>
                <EmptyState
                    title="Learning management coming soon"
                    description="Course assignments, completions, and expiries will appear here."
                />
            </Card>
        </AppLayout>
    );
}
