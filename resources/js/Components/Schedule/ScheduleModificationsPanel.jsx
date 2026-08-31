import { router } from '@inertiajs/react';
import { Check, Lock, Undo2, UploadCloud } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/utils/helpers';

const TABS = [
    { key: 'drafts', label: 'Schedule Drafts' },
    { key: 'requests', label: 'Modification Requests' },
];

export default function ScheduleModificationsPanel({
    drafts = [],
    requests = [],
    selectedProjectId = null,
    canEdit = false,
}) {
    const [active, setActive] = useState('drafts');
    const counts = { drafts: drafts.length, requests: requests.filter((item) => item.status === 'pending').length };
    const busy = drafts.length === 0;

    const post = (name) => {
        router.post(
            route(name),
            selectedProjectId ? { project_id: selectedProjectId } : {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="card">
            <div className="flex flex-wrap items-start justify-between gap-3 px-4 pt-3">
                <h2 className="text-sm font-semibold text-slate-900">Schedule Modifications – Draft</h2>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        disabled={!canEdit || busy}
                        onClick={() => canEdit && !busy && post('schedule.reset')}
                        title={canEdit ? 'Discard unpublished cell changes' : 'You cannot edit the schedule'}
                        className="btn-secondary min-h-8 px-2.5 py-1.5 text-xs disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Reset All Changes
                        <Undo2 className="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        disabled={!canEdit || busy}
                        onClick={() => canEdit && !busy && post('schedule.publish')}
                        title={canEdit ? 'Publish drafts and sync lodge reservations' : 'You cannot edit the schedule'}
                        className="btn-primary min-h-8 px-2.5 py-1.5 text-xs disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Publish All
                        <UploadCloud className="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <div className="mt-2 flex gap-5 border-b border-slate-200 px-4">
                {TABS.map((tab) => {
                    const isActive = tab.key === active;

                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => setActive(tab.key)}
                            className={cn(
                                '-mb-px flex items-center gap-1.5 border-b-2 pb-2 text-xs transition',
                                isActive
                                    ? 'border-brand font-semibold text-brand'
                                    : 'border-transparent font-medium text-slate-500 hover:text-slate-700',
                            )}
                        >
                            {tab.label}
                            {counts[tab.key] > 0 && (
                                <span className="rounded-full bg-slate-100 px-1.5 text-[10px] font-semibold text-slate-600">
                                    {counts[tab.key]}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            {active === 'drafts' && (
                <div className="px-4 py-3">
                    {drafts.length === 0 ? (
                        <p className="flex items-center justify-center gap-2 py-4 text-center text-xs text-slate-500">
                            <Lock className="h-3.5 w-3.5 text-slate-400" />
                            No draft changes. Drag or right-click the board to stage edits before publishing.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {drafts.map((draft) => (
                                <li key={draft.id} className="flex items-center justify-between gap-3 py-2 text-xs">
                                    <div>
                                        <p className="font-medium text-slate-800">{draft.worker_name}</p>
                                        <p className="text-slate-500">
                                            {draft.project_name} · {draft.summary}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-warning-soft px-2 py-0.5 text-[10px] font-semibold text-warning">
                                        {draft.change_count} pending
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            {active === 'requests' && (
                <div className="px-4 py-3">
                    {requests.length === 0 ? (
                        <p className="flex items-center justify-center gap-2 py-4 text-center text-xs text-slate-500">
                            <Lock className="h-3.5 w-3.5 text-slate-400" />
                            No reservation modifications yet. Publish a draft to sync lodge stays.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {requests.map((item) => (
                                <li key={item.id} className="flex items-center justify-between gap-3 py-2 text-xs">
                                    <div>
                                        <p className="font-medium text-slate-800">{item.worker_name}</p>
                                        <p className="text-slate-500">
                                            {item.project_name} · {item.check_in || '—'} → {item.check_out || '—'}
                                            {item.previous_check_in && (
                                                <span className="text-slate-400">
                                                    {' '}
                                                    (was {item.previous_check_in} → {item.previous_check_out || '—'})
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                    {item.status === 'pending' && canEdit ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    route('schedule.requests.acknowledge', item.id),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className="btn-secondary min-h-7 px-2 py-1 text-[11px]"
                                        >
                                            <Check className="h-3 w-3" />
                                            Acknowledge
                                        </button>
                                    ) : (
                                        <span className="rounded-full bg-success-soft px-2 py-0.5 text-[10px] font-semibold text-success">
                                            {item.status}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
