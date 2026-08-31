import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Badge from '@/Components/Shared/Badge';
import Button from '@/Components/Shared/Button';
import EmptyState from '@/Components/Shared/EmptyState';
import Select from '@/Components/Shared/Select';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';
import SettingsLayout from '@/Layouts/SettingsLayout';

function statusValue(status) {
    return status?.value || status;
}

export default function SettingsModules({
    modules = [],
    companies = [],
}) {
    const moduleList = Array.isArray(modules) ? modules : modules.data || [];
    const [selectedModuleId, setSelectedModuleId] = useState(moduleList[0]?.id || null);
    const [grantCompanyId, setGrantCompanyId] = useState('');
    const [processingKey, setProcessingKey] = useState(null);

    const selectedModule = useMemo(
        () => moduleList.find((module) => module.id === selectedModuleId) || moduleList[0] || null,
        [moduleList, selectedModuleId],
    );

    const run = (key, callback) => {
        setProcessingKey(key);
        callback({
            preserveScroll: true,
            onFinish: () => setProcessingKey(null),
        });
    };

    return (
        <SettingsLayout
            title="Settings"
            pageTitle="Modules"
            subtitle="Configure paid modules and organization access."
        >
            <div className="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                <div className="card overflow-hidden">
                    <div className="border-b border-slate-100 px-4 py-3">
                        <h2 className="text-sm font-semibold text-slate-900">Platform modules</h2>
                        <p className="text-xs text-slate-500">
                            Sidebar modules in navigation order. Major Projects is paid by default.
                        </p>
                    </div>
                    <div className="table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Status</th>
                                    <th>Paid</th>
                                    <th>Pending</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {moduleList.map((module) => (
                                    <tr
                                        key={module.id}
                                        className={
                                            selectedModule?.id === module.id
                                                ? 'bg-brand-soft/30'
                                                : undefined
                                        }
                                    >
                                        <td>
                                            <button
                                                type="button"
                                                className="text-left"
                                                onClick={() => setSelectedModuleId(module.id)}
                                            >
                                                <div className="font-medium text-slate-900">
                                                    {module.name}
                                                </div>
                                                <div className="text-[11px] text-slate-500">
                                                    {module.description || module.key}
                                                </div>
                                            </button>
                                        </td>
                                        <td>
                                            <Badge tone={module.is_active ? 'success' : 'slate'}>
                                                {module.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td>
                                            <ToggleSwitch
                                                size="sm"
                                                checked={Boolean(module.is_paid)}
                                                disabled={processingKey === `paid-${module.id}`}
                                                label={`Toggle paid for ${module.name}`}
                                                onChange={(checked) =>
                                                    run(`paid-${module.id}`, (options) =>
                                                        router.patch(
                                                            route('settings.modules.paid', module.id),
                                                            { is_paid: checked },
                                                            options,
                                                        ),
                                                    )
                                                }
                                            />
                                        </td>
                                        <td>{module.pending_requests_count || 0}</td>
                                        <td>
                                            <button
                                                type="button"
                                                className="text-xs font-medium text-brand hover:underline"
                                                onClick={() => setSelectedModuleId(module.id)}
                                            >
                                                Manage access
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="card p-4">
                        <h3 className="text-sm font-semibold text-slate-900">
                            Organization access
                            {selectedModule ? ` · ${selectedModule.name}` : ''}
                        </h3>
                        {!selectedModule ? (
                            <EmptyState
                                title="Select a module"
                                description="Choose a module to grant or revoke organization access."
                            />
                        ) : (
                            <>
                                <div className="mt-3 flex flex-wrap items-end gap-2">
                                    <div className="min-w-[180px] flex-1">
                                        <Select
                                            label="Organization"
                                            value={grantCompanyId}
                                            onChange={(e) => setGrantCompanyId(e.target.value)}
                                            placeholder="Select organization"
                                            options={companies.map((company) => ({
                                                value: company.id,
                                                label: company.name,
                                            }))}
                                        />
                                    </div>
                                    <Button
                                        disabled={
                                            !grantCompanyId || processingKey === `grant-${selectedModule.id}`
                                        }
                                        onClick={() =>
                                            run(`grant-${selectedModule.id}`, (options) =>
                                                router.post(
                                                    route('settings.modules.grant', selectedModule.id),
                                                    { company_id: grantCompanyId, status: 'active' },
                                                    {
                                                        ...options,
                                                        onSuccess: () => setGrantCompanyId(''),
                                                    },
                                                ),
                                            )
                                        }
                                    >
                                        Grant access
                                    </Button>
                                </div>

                                <div className="mt-4 space-y-2">
                                    {(selectedModule.companies_with_access || []).length === 0 ? (
                                        <p className="text-sm text-slate-500">
                                            No organizations have an entitlement row yet.
                                            {selectedModule.is_paid
                                                ? ' Paid modules require an active entitlement.'
                                                : ' Free modules are available to all organizations by default.'}
                                        </p>
                                    ) : (
                                        selectedModule.companies_with_access.map((row) => (
                                            <div
                                                key={row.id}
                                                className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2"
                                            >
                                                <div>
                                                    <p className="text-sm font-medium text-slate-800">
                                                        {row.company_name}
                                                    </p>
                                                    <p className="text-[11px] text-slate-500">
                                                        {statusValue(row.status)}
                                                        {row.activation_source
                                                            ? ` · ${statusValue(row.activation_source)}`
                                                            : ''}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="secondary"
                                                    className="min-h-8 px-2.5 text-xs"
                                                    disabled={
                                                        processingKey === `revoke-${selectedModule.id}-${row.company_id}`
                                                    }
                                                    onClick={() =>
                                                        run(
                                                            `revoke-${selectedModule.id}-${row.company_id}`,
                                                            (options) =>
                                                                router.post(
                                                                    route(
                                                                        'settings.modules.revoke',
                                                                        selectedModule.id,
                                                                    ),
                                                                    { company_id: row.company_id },
                                                                    options,
                                                                ),
                                                        )
                                                    }
                                                >
                                                    Revoke
                                                </Button>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </SettingsLayout>
    );
}
