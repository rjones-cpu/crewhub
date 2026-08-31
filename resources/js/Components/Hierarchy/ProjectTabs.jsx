import { router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '@/utils/helpers';

const SCROLL_STEP = 240;

/**
 * One tab per major project the company belongs to. Selecting a tab switches the
 * session project, which is what reloads the hierarchy for that project.
 */
export default function ProjectTabs({ activeProjectId = null }) {
    const { majorProjects = [] } = usePage().props;
    const scrollerRef = useRef(null);
    const [overflowing, setOverflowing] = useState(false);

    const measure = useCallback(() => {
        const scroller = scrollerRef.current;

        if (scroller) {
            setOverflowing(scroller.scrollWidth > scroller.clientWidth + 1);
        }
    }, []);

    useEffect(() => {
        measure();
        window.addEventListener('resize', measure);

        return () => window.removeEventListener('resize', measure);
    }, [measure, majorProjects.length]);

    // Keep the selected project visible when it sits outside the scrolled area.
    useEffect(() => {
        scrollerRef.current
            ?.querySelector('[data-active="true"]')
            ?.scrollIntoView({ inline: 'center', block: 'nearest' });
    }, [activeProjectId]);

    if (majorProjects.length === 0) {
        return null;
    }

    const scroll = (amount) => {
        scrollerRef.current?.scrollBy({ left: amount, behavior: 'smooth' });
    };

    return (
        <div className="card overflow-hidden">
            <div className="flex items-stretch border-b border-slate-100">
                {overflowing && (
                    <button
                        type="button"
                        onClick={() => scroll(-SCROLL_STEP)}
                        aria-label="Scroll projects left"
                        className="shrink-0 px-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>
                )}

                <div
                    ref={scrollerRef}
                    className="no-scrollbar flex min-w-0 flex-1 gap-6 overflow-x-auto px-4"
                >
                    {majorProjects.map((project) => {
                        const active = project.id === activeProjectId;

                        return (
                            <button
                                key={project.id}
                                type="button"
                                data-active={active}
                                onClick={() =>
                                    router.post(
                                        route('major-projects.switch', project.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className={cn(
                                    '-mb-px flex shrink-0 flex-col items-start whitespace-nowrap border-b-2 px-1 pb-2 pt-3 text-left transition',
                                    active
                                        ? 'border-brand text-brand'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                )}
                            >
                                <span className={cn('text-sm', active ? 'font-semibold' : 'font-medium')}>
                                    {project.name}
                                </span>
                                <span className="text-[11px] text-slate-400">
                                    {project.project_number || project.code || project.location || '—'}
                                </span>
                            </button>
                        );
                    })}
                </div>

                {overflowing && (
                    <button
                        type="button"
                        onClick={() => scroll(SCROLL_STEP)}
                        aria-label="Scroll projects right"
                        className="shrink-0 px-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                )}
            </div>
        </div>
    );
}
