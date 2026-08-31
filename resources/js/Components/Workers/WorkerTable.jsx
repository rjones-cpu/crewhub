import { Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import EmptyState from '@/Components/Shared/EmptyState';
import { cn } from '@/utils/helpers';

export default function WorkerTable({
    workers = [],
    selectedId,
    onSelect,
    selectedIds = [],
    onToggleSelect,
}) {
    if (!workers.length) {
        return (
            <div className="card">
                <EmptyState
                    title="No workers found"
                    description="Adjust filters or add a new worker to get started."
                />
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-md border border-slate-200">
            <div className="table-wrap">
                <table className="w-full table-fixed text-left text-[9px]">
                    <colgroup>
                        <col className="w-[25%]" />
                        <col className="w-[14%]" />
                        <col className="w-[24%]" />
                        <col className="w-[12%]" />
                        <col className="w-[17%]" />
                        <col className="w-[8%]" />
                    </colgroup>
                    <thead>
                        <tr className="bg-slate-50 text-slate-600">
                            {onToggleSelect && <th className="w-10" />}
                            <th className="px-3 py-2 font-semibold">Worker</th>
                            <th className="px-3 py-2 font-semibold">Role</th>
                            <th className="px-3 py-2 font-semibold">Primary Project</th>
                            <th className="px-3 py-2 font-semibold">Status</th>
                            <th className="px-3 py-2 font-semibold">Last Active</th>
                            <th className="px-3 py-2 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {workers.map((worker) => {
                            const active = selectedId === worker.id;
                            const checked = selectedIds.includes(worker.id);

                            return (
                                <tr
                                    key={worker.id}
                                    onClick={() => onSelect?.(worker)}
                                    className={cn(
                                        'hover:bg-slate-50',
                                        onSelect && 'cursor-pointer',
                                        active && 'bg-brand-soft/60',
                                    )}
                                >
                                    {onToggleSelect && (
                                        <td onClick={(e) => e.stopPropagation()}>
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() => onToggleSelect(worker.id)}
                                                className="rounded border-slate-300 text-brand focus:ring-brand"
                                            />
                                        </td>
                                    )}
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <Avatar
                                                name={worker.full_name}
                                                src={worker.avatar}
                                                size="sm"
                                                className="h-7 w-7 text-[8px]"
                                            />
                                            <div className="min-w-0">
                                                <p className="truncate font-medium text-slate-900">{worker.full_name}</p>
                                                <p className="truncate text-[8px] text-slate-500">ID: {worker.employee_id}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="truncate px-3 py-2 text-slate-700">{worker.position || '—'}</td>
                                    <td className="px-3 py-2">
                                        <p className="truncate text-slate-800">{worker.primary_project?.name || '—'}</p>
                                        {worker.primary_project?.code && (
                                            <p className="truncate text-[8px] text-slate-400">{worker.primary_project.code}</p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <Badge status={worker.status} className="px-1.5 py-0.5 text-[8px]" />
                                    </td>
                                    <td className="px-3 py-2 text-slate-600">
                                        <p>{worker.last_activity?.date || '—'}</p>
                                        <p className="text-[8px] text-slate-400">{worker.last_activity?.time || '—'}</p>
                                    </td>
                                    <td className="px-3 py-2 text-center" onClick={(event) => event.stopPropagation()}>
                                        <Dropdown>
                                            <Dropdown.Trigger>
                                                <button
                                                    type="button"
                                                    className="inline-grid h-6 w-6 place-items-center rounded text-slate-500 hover:bg-slate-100"
                                                    aria-label={`Actions for ${worker.full_name}`}
                                                >
                                                    <MoreVertical className="h-3.5 w-3.5" />
                                                </button>
                                            </Dropdown.Trigger>
                                            <Dropdown.Content width="48" contentClasses="bg-white py-1">
                                                <Link
                                                    href={route('workers.show', worker.id)}
                                                    className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                                                >
                                                    View worker
                                                </Link>
                                                <Link
                                                    href={route('workers.edit', worker.id)}
                                                    className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                                                >
                                                    Edit worker
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        if (window.confirm(`Archive ${worker.full_name}?`)) {
                                                            router.delete(route('workers.destroy', worker.id));
                                                        }
                                                    }}
                                                    className="block w-full px-3 py-1.5 text-left text-[9px] text-rose-600 hover:bg-rose-50"
                                                >
                                                    Archive worker
                                                </button>
                                            </Dropdown.Content>
                                        </Dropdown>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
