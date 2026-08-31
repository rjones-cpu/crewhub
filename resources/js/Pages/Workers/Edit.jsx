import { Head, Link, useForm } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';
import AppLayout from '@/Layouts/AppLayout';

export default function WorkersEdit({ worker, projects = [], positions = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: worker.employee_id || '',
        first_name: worker.first_name || '',
        last_name: worker.last_name || '',
        email: worker.email || '',
        phone: worker.phone || '',
        position: worker.position || '',
        location: worker.location || '',
        status: worker.status || 'active',
        on_site: Boolean(worker.on_site),
        primary_project_id: worker.primary_project?.id || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('workers.update', worker.id));
    };

    return (
        <AppLayout title={`Edit ${worker.full_name}`} subtitle="Update worker profile">
            <Head title={`Edit ${worker.full_name}`} />

            <Card className="max-w-3xl">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Employee ID"
                            value={data.employee_id}
                            onChange={(e) => setData('employee_id', e.target.value)}
                            error={errors.employee_id}
                        />
                        <Select
                            label="Status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            error={errors.status}
                            options={[
                                { value: 'active', label: 'Active' },
                                { value: 'inactive', label: 'Inactive' },
                                { value: 'on_leave', label: 'On Leave' },
                                { value: 'mobilizing', label: 'Mobilizing' },
                                { value: 'demobilizing', label: 'Demobilizing' },
                            ]}
                        />
                        <Input
                            label="First name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            error={errors.first_name}
                            required
                        />
                        <Input
                            label="Last name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            error={errors.last_name}
                            required
                        />
                        <Input
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                        <Input
                            label="Phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />
                        {positions.length > 0 ? (
                            <Select
                                label="Position"
                                value={data.position}
                                onChange={(e) => setData('position', e.target.value)}
                                error={errors.position}
                                placeholder="Select position"
                                options={
                                    data.position && !positions.includes(data.position)
                                        ? [data.position, ...positions]
                                        : positions
                                }
                            />
                        ) : (
                            <Input
                                label="Position"
                                value={data.position}
                                onChange={(e) => setData('position', e.target.value)}
                                error={errors.position}
                            />
                        )}
                        <Input
                            label="Location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            error={errors.location}
                        />
                        <Select
                            label="Primary project"
                            value={data.primary_project_id}
                            onChange={(e) => setData('primary_project_id', e.target.value)}
                            error={errors.primary_project_id}
                            placeholder="Select project"
                            options={projects.map((project) => ({
                                value: project.id,
                                label: project.name,
                            }))}
                        />
                        <label className="flex min-h-10 items-end gap-2 pb-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                checked={data.on_site}
                                onChange={(e) => setData('on_site', e.target.checked)}
                                className="rounded border-slate-300 text-brand focus:ring-brand"
                            />
                            Currently on site
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button as={Link} href={route('workers.show', worker.id)} variant="secondary">
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
