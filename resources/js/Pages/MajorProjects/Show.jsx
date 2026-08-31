import { Head, Link } from '@inertiajs/react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/utils/formatters';

export default function MajorProjectsShow({ project }) {
    const status = project.status?.value || project.status;

    return (
        <AppLayout title={project.name} subtitle={project.code || 'Major project'}>
            <Head title={project.name} />

            <div className="mb-4 flex flex-wrap gap-2">
                <Link href={route('major-projects.index')} className="btn-secondary min-h-10">
                    Back
                </Link>
                <Link href={route('major-projects.edit', project.id)} className="btn-primary min-h-10">
                    Edit
                </Link>
            </div>

            <Card>
                <div className="mb-4">
                    <Badge status={status} />
                </div>
                <dl className="grid gap-4 sm:grid-cols-2 text-sm">
                    {[
                        ['Code', project.code || '—'],
                        ['Location', project.location || '—'],
                        ['Type', project.project_type || '—'],
                        ['Workers', formatNumber(project.workers_count)],
                        ['Start', formatDate(project.start_date)],
                        ['End', formatDate(project.end_date)],
                    ].map(([label, value]) => (
                        <div key={label}>
                            <dt className="text-slate-500">{label}</dt>
                            <dd className="mt-1 font-medium text-slate-900">{value}</dd>
                        </div>
                    ))}
                </dl>
                {project.description && (
                    <div className="mt-6 border-t border-slate-100 pt-4">
                        <p className="text-sm text-slate-500">Description</p>
                        <p className="mt-1 text-sm text-slate-800">{project.description}</p>
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
