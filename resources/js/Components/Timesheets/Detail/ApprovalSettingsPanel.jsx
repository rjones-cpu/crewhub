import { BadgeCheck, ShieldCheck, Sparkles, UserCheck } from 'lucide-react';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';

const SETTINGS = [
    {
        key: 'worker',
        title: 'Worker Approval',
        description: 'Worker must approve their timesheet before submission.',
        icon: UserCheck,
    },
    {
        key: 'manager',
        title: 'Manager Approval',
        description: 'Manager must review and approve.',
        icon: ShieldCheck,
    },
    {
        key: 'client',
        title: 'Client Approval',
        description: 'Client must review and approve.',
        icon: BadgeCheck,
    },
    {
        key: 'ai_accommodations',
        title: 'AI Accommodations Confirmation',
        description: 'AI will confirm worker stayed in accommodations.',
        icon: Sparkles,
    },
];

export default function ApprovalSettingsPanel({
    settings = {},
    editable,
    showClient = false,
    onChange,
}) {
    const visibleSettings = SETTINGS.filter((setting) => setting.key !== 'client' || showClient);

    return (
        <div className="card card-padding">
            <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                Approval Settings
            </h2>
            <p className="mt-1 text-[11px] text-slate-500">
                Configure the approval steps for this project.
            </p>

            <ul className="mt-3 divide-y divide-slate-100">
                {visibleSettings.map(({ key, title, description, icon: Icon }) => {
                    const enabled = Boolean(settings[key]);

                    return (
                        <li key={key} className="flex items-start gap-3 py-3">
                            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <Icon className="h-4 w-4" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-xs font-medium text-slate-900">{title}</p>
                                <p className="text-[11px] leading-snug text-slate-500">{description}</p>
                                {enabled && (
                                    <span className="badge mt-1.5 bg-success-soft text-[10px] text-success">
                                        Required
                                    </span>
                                )}
                            </div>
                            <ToggleSwitch
                                label={title}
                                checked={enabled}
                                disabled={!editable}
                                onChange={(value) => onChange(key, value)}
                            />
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
