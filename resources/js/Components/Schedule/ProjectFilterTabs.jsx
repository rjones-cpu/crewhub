import { router } from '@inertiajs/react';
import { ChevronDown, LayoutGrid, Users } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import { formatNumber } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import { VISIBLE_PROJECT_CHIPS } from './scheduleConstants';

function workerLabel(count) {
    return `${formatNumber(count ?? 0)} ${count === 1 ? 'Worker' : 'Workers'}`;
}

export default function ProjectFilterTabs({
    projects = [],
    selectedProjectId = null,
    totalWorkerCount = 0,
    params = {},
    variant = 'cards',
}) {
    const select = (projectId) => {
        router.get(
            route('schedule.index'),
            { ...params, ...(projectId ? { project_id: projectId } : {}) },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const visible = projects.slice(0, VISIBLE_PROJECT_CHIPS);
    const overflow = projects.slice(VISIBLE_PROJECT_CHIPS);
    const selectedInOverflow = overflow.some((project) => project.id === selectedProjectId);

    if (variant === 'camp') {
        const tabs = [
            { id: null, name: 'All' },
            ...projects.map((project) => ({
                id: project.id,
                name: project.reference || project.name,
                title: project.name,
            })),
        ];

        return (
            <div className="flex min-h-8 items-stretch gap-0.5 overflow-x-auto border-b-2 border-brand pb-1">
                {tabs.map((tab) => {
                    const active = tab.id === selectedProjectId;

                    return (
                        <button
                            key={tab.id ?? 'all'}
                            type="button"
                            title={tab.title || tab.name}
                            onClick={() => select(tab.id)}
                            className={cn(
                                'min-w-[76px] shrink-0 truncate px-3 py-2 text-[9px] font-bold uppercase tracking-wide transition',
                                active
                                    ? 'bg-brand text-white'
                                    : 'bg-[#168bea] text-white hover:bg-brand-hover',
                            )}
                            style={{
                                clipPath:
                                    'polygon(7px 0, calc(100% - 7px) 0, 100% 7px, calc(100% - 4px) 100%, 4px 100%, 0 7px)',
                            }}
                        >
                            {tab.name}
                        </button>
                    );
                })}
            </div>
        );
    }

    return (
        <div className="flex items-stretch gap-2 overflow-x-auto pb-0.5">
            <button
                type="button"
                onClick={() => select(null)}
                className={cn(
                    'flex shrink-0 items-center gap-2 rounded-lg border px-3 py-1.5 transition',
                    selectedProjectId === null
                        ? 'border-brand bg-brand text-white shadow-card'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
                )}
            >
                <LayoutGrid className="h-4 w-4 shrink-0" />
                <span className="flex flex-col items-start leading-tight">
                    <span className="whitespace-nowrap text-[11px] font-semibold">All Projects</span>
                    <span
                        className={cn(
                            'whitespace-nowrap text-[10px]',
                            selectedProjectId === null ? 'text-white/80' : 'text-slate-500',
                        )}
                    >
                        {workerLabel(totalWorkerCount)}
                    </span>
                </span>
            </button>

            {visible.map((project) => {
                const active = project.id === selectedProjectId;

                return (
                    <button
                        key={project.id}
                        type="button"
                        onClick={() => select(project.id)}
                        title={project.name}
                        className={cn(
                            'flex shrink-0 items-center gap-2 rounded-lg border px-3 py-1.5 text-left transition',
                            active
                                ? 'border-brand bg-brand text-white shadow-card'
                                : 'border-slate-200 bg-white hover:border-slate-300',
                        )}
                    >
                        <Users
                            className={cn('h-4 w-4 shrink-0', active ? 'text-white' : 'text-slate-400')}
                        />
                        <span className="flex flex-col items-start leading-tight">
                            <span
                                className={cn(
                                    'max-w-[120px] truncate text-[11px] font-semibold',
                                    active ? 'text-white' : 'text-slate-900',
                                )}
                            >
                                {project.name}
                            </span>
                            <span
                                className={cn(
                                    'whitespace-nowrap text-[10px]',
                                    active ? 'text-white/80' : 'text-slate-500',
                                )}
                            >
                                {workerLabel(project.worker_count)}
                            </span>
                        </span>
                    </button>
                );
            })}

            {overflow.length > 0 && (
                <Dropdown>
                    <Dropdown.Trigger>
                        <button
                            type="button"
                            className={cn(
                                'flex h-full shrink-0 items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-medium transition',
                                selectedInOverflow
                                    ? 'border-brand bg-brand-soft text-brand'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
                            )}
                        >
                            More Projects
                            <ChevronDown className="h-3.5 w-3.5" />
                        </button>
                    </Dropdown.Trigger>
                    <Dropdown.Content width="48" contentClasses="bg-white py-1">
                        {overflow.map((project) => (
                            <button
                                key={project.id}
                                type="button"
                                onClick={() => select(project.id)}
                                className={cn(
                                    'flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-xs hover:bg-slate-50',
                                    project.id === selectedProjectId
                                        ? 'font-semibold text-brand'
                                        : 'text-slate-700',
                                )}
                            >
                                <span className="truncate">{project.name}</span>
                                <span className="shrink-0 text-[10px] text-slate-400">
                                    {formatNumber(project.worker_count ?? 0)}
                                </span>
                            </button>
                        ))}
                    </Dropdown.Content>
                </Dropdown>
            )}
        </div>
    );
}
