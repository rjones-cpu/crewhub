import { router, usePage } from '@inertiajs/react';
import { Check, ChevronDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/utils/helpers';

export default function ProjectSelector() {
    const { majorProjects = [], currentProject } = usePage().props;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        const onClick = (event) => {
            if (ref.current && !ref.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onClick);

        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    const switchProject = (id) => {
        setOpen(false);
        router.post(route('major-projects.switch', id), {}, { preserveState: true });
    };

    const showAllProjects = () => {
        setOpen(false);
        router.post(route('major-projects.clear'), {}, { preserveState: true });
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex h-8 w-full items-center justify-between gap-1.5 rounded-md border border-white/25 bg-sidebar-dark px-2.5 text-[10px] font-medium text-white transition hover:bg-white/5"
            >
                <span className="truncate">
                    {currentProject?.name || 'All Projects'}
                </span>
                <ChevronDown className="h-3.5 w-3.5 shrink-0 text-slate-300" />
            </button>

            {open && (
                <div className="absolute left-0 z-40 mt-2 w-full min-w-[16rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                    <div className="border-b border-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Major Projects
                    </div>
                    <ul className="max-h-64 overflow-y-auto py-1">
                        <li>
                            <button
                                type="button"
                                onClick={showAllProjects}
                                className={cn(
                                    'flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm font-medium transition hover:bg-slate-50',
                                    currentProject ? 'text-slate-700' : 'text-brand',
                                )}
                            >
                                All Projects
                                {!currentProject && <Check className="h-4 w-4 shrink-0" />}
                            </button>
                        </li>
                        {majorProjects.length === 0 && (
                            <li className="px-3 py-2 text-sm text-slate-500">No projects available</li>
                        )}
                        {majorProjects.map((project) => {
                            const active = currentProject?.id === project.id;

                            return (
                                <li key={project.id}>
                                    <button
                                        type="button"
                                        onClick={() => switchProject(project.id)}
                                        className={cn(
                                            'flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm transition hover:bg-slate-50',
                                            active ? 'text-brand' : 'text-slate-700',
                                        )}
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium">{project.name}</span>
                                            {project.code && (
                                                <span className="block truncate text-xs text-slate-400">
                                                    {project.code}
                                                </span>
                                            )}
                                        </span>
                                        {active && <Check className="h-4 w-4 shrink-0" />}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}
        </div>
    );
}
