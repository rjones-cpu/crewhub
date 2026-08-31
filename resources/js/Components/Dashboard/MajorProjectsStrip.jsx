import { Link, router, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    Compass,
    Factory,
    HardHat,
    LayoutGrid,
    Mountain,
    Plus,
    Route,
    Waves,
    Wind,
    Zap,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { cn } from '@/utils/helpers';

const VISIBLE_LIMIT = 5;

// Named icons come from the project's `icon` column; anything else falls back to a
// stable per-project glyph so the strip stays visually distinguishable.
const NAMED_ICONS = { Compass, Factory, HardHat, Mountain, Route, Waves, Wind, Zap };
const FALLBACK_ICONS = [Zap, Waves, Route, Wind, Mountain];

function projectIcon(project) {
    return NAMED_ICONS[project.icon] || FALLBACK_ICONS[(project.id ?? 0) % FALLBACK_ICONS.length];
}

// `compact` is the tiny variant used on Readiness; `dense` keeps readable type
// but trims the padding so the strip fits a single-viewport dashboard.
export default function MajorProjectsStrip({
    compact = false,
    dense = false,
    showAdd = true,
    showAll = true,
}) {
    const { majorProjects = [], currentProject, auth } = usePage().props;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    // Only company managers create projects; Super Admin approves module activation instead.
    const canCreateProject = ['company_admin', 'workforce_manager'].includes(auth?.user?.role);
    const showAddButton = showAdd && canCreateProject;

    useEffect(() => {
        const onClick = (event) => {
            if (ref.current && !ref.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onClick);

        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    const { visible, overflow } = useMemo(() => {
        const list = [...majorProjects];
        const activeIndex = list.findIndex((project) => project.id === currentProject?.id);

        // Pull the selected project into the visible row when it would otherwise overflow.
        if (activeIndex >= VISIBLE_LIMIT) {
            const [active] = list.splice(activeIndex, 1);
            list.splice(VISIBLE_LIMIT - 1, 0, active);
        }

        return {
            visible: list.slice(0, VISIBLE_LIMIT),
            overflow: list.slice(VISIBLE_LIMIT),
        };
    }, [majorProjects, currentProject?.id]);

    const switchProject = (id) => {
        setOpen(false);
        router.post(route('major-projects.switch', id), {}, { preserveScroll: true });
    };

    const showAllProjects = () => {
        setOpen(false);
        router.post(route('major-projects.clear'), {}, { preserveScroll: true });
    };

    return (
        <div className={cn('card', compact || dense ? 'rounded-lg p-3' : 'card-padding')}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2
                    className={cn(
                        'font-semibold text-slate-900',
                        compact ? 'text-[10px]' : dense ? 'text-sm' : 'text-base',
                    )}
                >
                    Major Projects
                </h2>
                {showAddButton && (
                    <Link
                        href={route('major-projects.create')}
                        className={cn('btn-primary', dense ? 'min-h-8 py-1 text-xs' : 'min-h-10')}
                    >
                        <Plus className={dense ? 'h-3.5 w-3.5' : 'h-4 w-4'} />
                        Add Major Project
                    </Link>
                )}
            </div>

            {majorProjects.length === 0 ? (
                <p className="mt-3 text-sm text-slate-500">
                    No active projects yet. Add one to start tracking workforce by project.
                </p>
            ) : (
                <div
                    className={cn(
                        'flex flex-wrap items-center gap-2',
                        compact ? 'mt-2' : dense ? 'mt-2.5' : 'mt-4',
                    )}
                >
                    {showAll && (
                        <button
                            type="button"
                            onClick={showAllProjects}
                            disabled={!currentProject}
                            className={cn(
                                'flex items-center rounded-lg border font-medium transition',
                                compact
                                    ? 'min-h-9 gap-2 px-3 text-[9px]'
                                    : dense
                                        ? 'min-h-9 gap-2 px-3 text-xs'
                                        : 'min-h-11 gap-2.5 px-4 text-sm',
                                currentProject
                                    ? 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                                    : 'border-brand bg-brand-soft text-brand shadow-sm',
                            )}
                        >
                            <LayoutGrid
                                className={cn(
                                    'h-4 w-4 shrink-0',
                                    currentProject ? 'text-slate-400' : 'text-brand',
                                )}
                            />
                            All Projects
                        </button>
                    )}

                    {visible.map((project) => {
                        const Icon = projectIcon(project);
                        const active = project.id === currentProject?.id;

                        return (
                            <button
                                key={project.id}
                                type="button"
                                onClick={() => switchProject(project.id)}
                                disabled={active}
                                className={cn(
                                    'flex items-center rounded-lg border font-medium transition',
                                    compact
                                        ? 'min-h-9 flex-1 gap-2 px-3 text-[9px]'
                                        : dense
                                            ? 'min-h-9 gap-2 px-3 text-xs'
                                            : 'min-h-11 gap-2.5 px-4 text-sm',
                                    active
                                        ? 'border-brand bg-brand-soft text-brand shadow-sm'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                )}
                            >
                                <Icon
                                    className={cn(
                                        'h-4 w-4 shrink-0',
                                        active ? 'text-brand' : 'text-slate-400',
                                    )}
                                />
                                <span className={cn('truncate', compact ? 'max-w-[130px]' : 'max-w-[180px]')}>
                                    {project.name}
                                </span>
                            </button>
                        );
                    })}

                    {overflow.length > 0 && (
                        <div className="relative" ref={ref}>
                            <button
                                type="button"
                                onClick={() => setOpen((value) => !value)}
                                className={cn(
                                    'flex items-center gap-2 rounded-lg border border-slate-200 bg-white font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50',
                                    compact
                                        ? 'min-h-9 px-3 text-[9px]'
                                        : dense
                                            ? 'min-h-9 px-3 text-xs'
                                            : 'min-h-11 px-4 text-sm',
                                )}
                            >
                                More Projects
                                <ChevronDown className="h-4 w-4 text-slate-400" />
                            </button>

                            {open && (
                                <div className="absolute left-0 z-40 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                    <ul className="max-h-64 overflow-y-auto py-1">
                                        {overflow.map((project) => (
                                            <li key={project.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => switchProject(project.id)}
                                                    className="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span className="min-w-0">
                                                        <span className="block truncate font-medium">
                                                            {project.name}
                                                        </span>
                                                        {project.location && (
                                                            <span className="block truncate text-xs text-slate-400">
                                                                {project.location}
                                                            </span>
                                                        )}
                                                    </span>
                                                    {project.id === currentProject?.id && (
                                                        <Check className="h-4 w-4 shrink-0 text-brand" />
                                                    )}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
