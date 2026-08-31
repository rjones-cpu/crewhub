import { Head, Link } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { unwrapPaginated } from '@/utils/helpers';

export default function WorkersActivity({ worker, activities }) {
    const { items, links, meta } = unwrapPaginated(activities);

    return (
        <AppLayout
            title={`${worker.full_name || worker.first_name || 'Worker'} Activity`}
            subtitle="Timeline of worker events"
        >
            <Head title="Worker Activity" />

            <div className="mb-4">
                <Link href={route('workers.show', worker.id)} className="btn-secondary min-h-10">
                    Back to profile
                </Link>
            </div>

            <Card title="Activity log">
                {items.length === 0 ? (
                    <EmptyState title="No activity yet" description="Events for this worker will show here." />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {items.map((activity) => (
                            <li key={activity.id} className="flex items-start justify-between gap-4 py-3 text-sm">
                                <div>
                                    <p className="font-medium text-slate-900">{activity.description}</p>
                                    <p className="text-slate-500">{activity.type}</p>
                                </div>
                                <span className="shrink-0 text-xs text-slate-400">
                                    {activity.created_at}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination links={links} meta={meta} className="mt-4" />
            </Card>
        </AppLayout>
    );
}
