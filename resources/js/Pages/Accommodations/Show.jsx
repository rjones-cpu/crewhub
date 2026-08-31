import { Head, Link } from '@inertiajs/react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { formatNumber } from '@/utils/formatters';

export default function AccommodationsShow({ accommodation }) {
    const assignments = accommodation.assignments || [];

    return (
        <AppLayout title={accommodation.name || `Accommodation #${accommodation.id}`} subtitle="Facility details">
            <Head title={accommodation.name || 'Accommodation'} />

            <div className="mb-4">
                <Link href={route('accommodations.index')} className="btn-secondary min-h-10">
                    Back
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card title="Details">
                    <dl className="grid gap-4 text-sm">
                        {[
                            ['Name', accommodation.name || '—'],
                            ['Project', accommodation.major_project?.name || accommodation.majorProject?.name || '—'],
                            ['Location', accommodation.location || '—'],
                            ['Capacity', formatNumber(accommodation.capacity)],
                            ['Occupied', formatNumber(accommodation.occupied ?? assignments.length)],
                        ].map(([label, value]) => (
                            <div key={label} className="flex justify-between gap-3">
                                <dt className="text-slate-500">{label}</dt>
                                <dd className="font-medium text-slate-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                    {accommodation.status && (
                        <div className="mt-4">
                            <Badge status={accommodation.status} />
                        </div>
                    )}
                </Card>

                <Card title="Assignments">
                    {assignments.length === 0 ? (
                        <EmptyState className="py-6" title="No assignments" description="Workers assigned here will show up." />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {assignments.map((assignment) => (
                                <li key={assignment.id} className="py-3 text-sm">
                                    <p className="font-medium text-slate-900">
                                        {assignment.worker?.full_name || assignment.worker?.name || 'Worker'}
                                    </p>
                                    <p className="text-slate-500">{assignment.status || 'Assigned'}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
