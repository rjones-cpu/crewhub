import { BusFront, CalendarDays, Clock3, GraduationCap, Lock } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/utils/helpers';

const FEATURES = [
    { key: 'schedule', label: 'Schedule', icon: CalendarDays },
    { key: 'timesheet', label: 'Timesheet', icon: Clock3 },
    { key: 'lms', label: 'Learning Management System', icon: GraduationCap },
    { key: 'journey', label: 'Journey Management', icon: BusFront },
];

// Paid modules cannot be self-served yet: activation goes through the Super
// Admin, the same flow Major Projects uses.
function ActivateButton({ moduleId, pending }) {
    const [processing, setProcessing] = useState(false);

    return (
        <button
            type="button"
            disabled={processing || pending || !moduleId}
            onClick={() => {
                setProcessing(true);
                router.post(
                    route('modules.request-activation', moduleId),
                    {},
                    { preserveScroll: true, onFinish: () => setProcessing(false) },
                );
            }}
            className="mt-1 rounded border border-indigo-200 bg-white px-1.5 py-0.5 text-[9px] font-semibold text-indigo-600 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400"
        >
            {pending ? 'Activation pending' : 'Activate'}
        </button>
    );
}

export default function WorkerFeaturesCard({ summary = {} }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <div className="mb-2.5">
                <h2 className="text-[11px] font-semibold text-slate-900">Worker Features</h2>
                <p className="mt-0.5 text-[9px] text-slate-500">
                    Feature availability across workers in Crew Hub.
                </p>
            </div>

            <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                {FEATURES.map(({ key, label, icon: Icon }) => {
                    const feature = summary[key] || {};
                    // Locked = the company has no active entitlement for a paid module,
                    // so the toggle only reads as on once the module is owned.
                    const locked = Boolean(feature.locked);
                    const enabled = Boolean(feature.enabled) && !locked;
                    const blockedCount = Number(feature.project_blocked_count || 0);
                    const total = Number(feature.total || 0);
                    const availability = locked
                        ? 'Not included in your plan'
                        : blockedCount > 0
                            ? `${feature.enabled_count || 0} enabled · ${blockedCount} blocked by projects`
                            : enabled ? 'Enabled for all workers' : 'Disabled for company workers';

                    return (
                        <div
                            key={key}
                            className={cn(
                                'flex min-h-[76px] items-center gap-2.5 rounded-md border px-3 py-2',
                                locked ? 'border-slate-200 bg-slate-50' : 'border-slate-200 bg-white',
                            )}
                            title={locked
                                ? `${label} requires module activation`
                                : `${feature.enabled_count || 0} of ${total} workers enabled`}
                        >
                            <div className={cn(
                                'grid h-8 w-8 shrink-0 place-items-center rounded-full',
                                locked ? 'bg-slate-200 text-slate-400' : 'bg-indigo-50 text-indigo-600',
                            )}>
                                {locked ? <Lock className="h-[15px] w-[15px]" /> : <Icon className="h-[18px] w-[18px]" />}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className={cn(
                                    'text-[10px] font-semibold leading-3',
                                    locked ? 'text-slate-400' : 'text-slate-900',
                                )}>
                                    {label}
                                </p>
                                <p className="mt-1 text-[9px] text-slate-500">{availability}</p>
                                {locked && feature.can_request_activation && (
                                    <ActivateButton
                                        moduleId={feature.module?.id}
                                        pending={Boolean(feature.activation_pending)}
                                    />
                                )}
                            </div>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={enabled}
                                aria-label={`${label} availability`}
                                disabled={locked || total === 0}
                                onClick={() => router.patch(
                                    route('workers.features.update', key),
                                    { enabled: !enabled },
                                    { preserveScroll: true },
                                )}
                                className={cn(
                                    'relative h-4 w-7 shrink-0 rounded-full disabled:cursor-not-allowed disabled:opacity-50',
                                    enabled ? 'bg-indigo-600' : 'bg-slate-200',
                                )}
                            >
                                <span
                                    className={cn(
                                        'absolute top-0.5 h-3 w-3 rounded-full bg-white shadow-sm transition',
                                        enabled ? 'left-3.5' : 'left-0.5',
                                    )}
                                />
                            </button>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
