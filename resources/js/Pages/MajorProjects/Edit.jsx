import { Head, Link, useForm } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';
import AppLayout from '@/Layouts/AppLayout';

export default function MajorProjectsEdit({ project }) {
    const { data, setData, put, processing, errors } = useForm({
        name: project.name || '',
        code: project.code || '',
        description: project.description || '',
        location: project.location || '',
        project_type: project.project_type || '',
        start_date: project.start_date || '',
        end_date: project.end_date || '',
        status: project.status?.value || project.status || 'active',
        icon: project.icon || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('major-projects.update', project.id));
    };

    return (
        <AppLayout title={`Edit ${project.name}`} subtitle="Update project details">
            <Head title={`Edit ${project.name}`} />

            <Card className="max-w-3xl">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />
                        <Input
                            label="Code"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={errors.code}
                            required
                        />
                        <Input
                            label="Location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            error={errors.location}
                        />
                        <Input
                            label="Project type"
                            value={data.project_type}
                            onChange={(e) => setData('project_type', e.target.value)}
                            error={errors.project_type}
                        />
                        <Input
                            label="Start date"
                            type="date"
                            value={data.start_date || ''}
                            onChange={(e) => setData('start_date', e.target.value)}
                            error={errors.start_date}
                        />
                        <Input
                            label="End date"
                            type="date"
                            value={data.end_date || ''}
                            onChange={(e) => setData('end_date', e.target.value)}
                            error={errors.end_date}
                        />
                        <Select
                            label="Status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            error={errors.status}
                            options={[
                                { value: 'planned', label: 'Planned' },
                                { value: 'active', label: 'Active' },
                                { value: 'completed', label: 'Completed' },
                                { value: 'archived', label: 'Archived' },
                            ]}
                        />
                        <Input
                            label="Icon"
                            value={data.icon}
                            onChange={(e) => setData('icon', e.target.value)}
                            error={errors.icon}
                        />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            className="input-field min-h-[100px]"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        {errors.description && (
                            <p className="mt-1.5 text-sm text-danger">{errors.description}</p>
                        )}
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button as={Link} href={route('major-projects.show', project.id)} variant="secondary">
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save Changes
                        </Button>
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}
