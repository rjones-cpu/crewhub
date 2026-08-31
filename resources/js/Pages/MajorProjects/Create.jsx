import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Lock, Plus } from 'lucide-react';
import { useState } from 'react';
import AddressMapPicker from '@/Components/MajorProjects/AddressMapPicker';
import ProjectSummaryPanel, { ModuleToggleGrid } from '@/Components/MajorProjects/ProjectSummaryPanel';
import { majorProjectTabs } from '@/Components/MajorProjects/projectHelpers';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';
import AppLayout from '@/Layouts/AppLayout';

function LockedModuleNotice({ module, pendingActivationRequest }) {
    const [processing, setProcessing] = useState(false);
    const pending = Boolean(pendingActivationRequest);

    const requestActivation = () => {
        if (!module?.id || pending) {
            return;
        }

        setProcessing(true);
        router.post(
            route('modules.request-activation', module.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="card mx-auto max-w-2xl p-8 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-warning-soft text-warning">
                <Lock className="h-5 w-5" />
            </div>
            <h2 className="mt-4 text-lg font-semibold text-slate-900">
                Major Project creation requires module activation
            </h2>
            <p className="mt-2 text-sm text-slate-600">
                Major Projects is a paid module. Activate this module to create and manage major
                projects. Online payments are not available yet, but you can request activation from
                the Super Admin.
            </p>
            <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                <Button
                    disabled={processing || pending || !module?.id}
                    onClick={requestActivation}
                >
                    {pending ? 'Request pending' : 'Request activation'}
                </Button>
                <Button as={Link} href={route('major-projects.index')} variant="secondary">
                    Back to projects
                </Button>
            </div>
            {pending && (
                <p className="mt-4 text-sm text-warning">
                    Your activation request is already pending.
                </p>
            )}
        </div>
    );
}

export default function MajorProjectsCreate({
    companies = [],
    managers = [],
    defaultModules = {},
    canCreate = false,
    canAttemptCreate = true,
    canJoin = false,
    hasMajorProjectsModule = false,
    module = null,
    pendingActivationRequest = null,
    organizationName = null,
}) {
    const { auth } = usePage().props;
    const ownCompanyId = auth?.user?.company_id;

    const { data, setData, post, processing, errors } = useForm({
        company_id: String(ownCompanyId || companies[0]?.id || ''),
        name: '',
        project_number: '',
        po_number: '',
        address: '',
        latitude: null,
        longitude: null,
        manager_id: '',
        start_date: '',
        end_date: '',
        comments: '',
        project_type: '',
        status: 'active',
        modules: { ...defaultModules },
    });

    const companyManagers = managers.filter(
        (manager) =>
            !data.company_id
            || String(manager.company_id) === String(data.company_id)
            || !manager.company_id,
    );

    const submit = (e) => {
        e.preventDefault();
        post(route('major-projects.store'));
    };

    return (
        <AppLayout
            title="Major Projects"
            subtitle="Create and manage connected major projects."
            showMeta={false}
            tabs={majorProjectTabs({ canAttemptCreate, canJoin })}
            activeTab="major-projects.create"
            tabsVariant="boxed"
        >
            <Head title="Create a Project" />

            {!hasMajorProjectsModule ? (
                <LockedModuleNotice
                    module={module}
                    pendingActivationRequest={pendingActivationRequest}
                />
            ) : (
                <form onSubmit={submit} className="relative">
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,340px)]">
                        <div className="card space-y-5 p-5">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">
                                    Create a New Project
                                </h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {`Enter a project name that is different from your organization name${organizationName ? ` (${organizationName})` : ''}.`}
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Project Name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                    placeholder="e.g. Blue Ridge Expansion"
                                    required
                                />
                                <Input
                                    label="Organization"
                                    value={organizationName || companies[0]?.name || ''}
                                    disabled
                                />
                                <div className="sm:col-span-2">
                                    <AddressMapPicker
                                        address={data.address}
                                        latitude={data.latitude}
                                        longitude={data.longitude}
                                        error={errors.address || errors.latitude || errors.longitude}
                                        onChange={({ address, latitude, longitude }) => {
                                            setData('address', address);
                                            setData('latitude', latitude);
                                            setData('longitude', longitude);
                                        }}
                                    />
                                </div>
                                <Input
                                    label="PO No."
                                    value={data.po_number}
                                    onChange={(e) => setData('po_number', e.target.value)}
                                    error={errors.po_number}
                                />
                                <Select
                                    label="Assign Manager"
                                    value={data.manager_id}
                                    onChange={(e) => setData('manager_id', e.target.value)}
                                    error={errors.manager_id}
                                    placeholder="Search and select a manager"
                                    options={companyManagers.map((manager) => ({
                                        value: manager.id,
                                        label: manager.name,
                                    }))}
                                />
                                <Input
                                    label="Project Number"
                                    value={data.project_number}
                                    onChange={(e) => setData('project_number', e.target.value)}
                                    error={errors.project_number}
                                />
                                <Input
                                    label="Project Type"
                                    value={data.project_type}
                                    onChange={(e) => setData('project_type', e.target.value)}
                                    error={errors.project_type}
                                />
                                <Input
                                    label="Start Date"
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    error={errors.start_date}
                                />
                                <Input
                                    label="End Date"
                                    type="date"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    error={errors.end_date}
                                />
                                <div className="sm:col-span-2">
                                    <div className="mb-1.5 flex items-center justify-between">
                                        <label className="block text-sm font-medium text-slate-700">
                                            Comments
                                        </label>
                                        <span className="text-[11px] text-slate-400">
                                            {(data.comments || '').length} / 500
                                        </span>
                                    </div>
                                    <textarea
                                        className="input-field min-h-[90px]"
                                        maxLength={500}
                                        value={data.comments}
                                        onChange={(e) => setData('comments', e.target.value)}
                                    />
                                    {errors.comments && (
                                        <p className="mt-1.5 text-sm text-danger">{errors.comments}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <h3 className="mb-2 text-sm font-semibold text-slate-900">
                                    Assign Worker Requirements
                                </h3>
                                <ModuleToggleGrid
                                    modules={data.modules}
                                    onChange={(key, checked) =>
                                        setData('modules', { ...data.modules, [key]: checked })
                                    }
                                />
                            </div>

                            <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                                <Button as={Link} href={route('major-projects.index')} variant="secondary">
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing || !canCreate}>
                                    <Plus className="h-4 w-4" />
                                    Create Project
                                </Button>
                            </div>
                        </div>

                        <div className="lg:sticky lg:top-24 lg:self-start">
                            <ProjectSummaryPanel
                                data={data}
                                companies={companies}
                                managers={managers}
                            />
                        </div>
                    </div>
                </form>
            )}
        </AppLayout>
    );
}
