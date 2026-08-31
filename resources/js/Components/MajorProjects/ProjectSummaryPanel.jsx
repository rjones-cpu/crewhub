import ToggleSwitch from '@/Components/Shared/ToggleSwitch';
import { enabledModuleLabels, PROJECT_MODULE_OPTIONS } from './projectHelpers';

function SummaryRow({ label, value }) {
    return (
        <div className="border-b border-slate-100 py-2.5 last:border-0">
            <p className="text-[11px] font-medium uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-0.5 text-sm font-medium text-slate-800">{value || '—'}</p>
        </div>
    );
}

export default function ProjectSummaryPanel({
    data,
    companies = [],
    managers = [],
}) {
    const company = companies.find((c) => String(c.id) === String(data.company_id));
    const manager = managers.find((m) => String(m.id) === String(data.manager_id));
    const modules = enabledModuleLabels(data.modules);

    return (
        <aside className="card flex h-full flex-col">
            <div className="border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Project Summary</h3>
                <p className="mt-0.5 text-[11px] text-slate-500">
                    Review the project details before creating.
                </p>
            </div>
            <div className="flex-1 overflow-y-auto px-4 py-1">
                <SummaryRow label="Project Name" value={data.name} />
                <SummaryRow label="Organization" value={company?.name} />
                <SummaryRow label="Project Number" value={data.project_number} />
                <SummaryRow label="PO No." value={data.po_number} />
                <SummaryRow label="Manager" value={manager?.name} />
                <SummaryRow label="Start Date" value={data.start_date} />
                <SummaryRow label="End Date" value={data.end_date} />
                <SummaryRow label="Address" value={data.address} />
                <div className="border-b border-slate-100 py-2.5">
                    <p className="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Modules Enabled
                    </p>
                    {modules.length === 0 ? (
                        <p className="mt-0.5 text-sm text-slate-500">No modules selected.</p>
                    ) : (
                        <ul className="mt-1.5 space-y-1">
                            {modules.map((label) => (
                                <li key={label} className="text-sm font-medium text-slate-800">
                                    {label}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div className="py-2.5">
                    <p className="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Comments
                    </p>
                    <p className="mt-0.5 whitespace-pre-wrap text-sm text-slate-700">
                        {data.comments || 'No comments.'}
                    </p>
                </div>
            </div>
        </aside>
    );
}

export function ModuleToggleGrid({ modules, onChange }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {PROJECT_MODULE_OPTIONS.map((module) => (
                <div
                    key={module.key}
                    className="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2.5"
                >
                    {/* min-w-0 lets the label shrink instead of pushing the toggle out of the card. */}
                    <span className="min-w-0 flex-1 break-words text-sm font-medium leading-tight text-slate-700">
                        {module.label}
                    </span>
                    <ToggleSwitch
                        size="sm"
                        checked={Boolean(modules?.[module.key])}
                        onChange={(checked) => onChange(module.key, checked)}
                        label={module.label}
                    />
                </div>
            ))}
        </div>
    );
}
