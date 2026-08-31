import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function Pagination({
    links = [],
    meta,
    className = '',
    compact = false,
    itemLabel = '',
    perPage,
    perPageOptions = [],
    onPerPageChange,
}) {
    const pageCount = Number(meta?.last_page || 0);
    const hasPageButtons = Array.isArray(links) && (links.length > 3 || pageCount > 1);
    const hasPerPage = perPageOptions.length > 0;

    if (!hasPageButtons && !hasPerPage && !meta) {
        return null;
    }

    const goTo = (url) => {
        if (!url) {
            return;
        }

        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <div className={cn(
            'flex flex-col border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between',
            compact ? 'gap-2 pt-2' : 'gap-3 pt-4',
            className,
        )}>
            {meta && (
                <p className={compact ? 'text-[7px] text-slate-500' : 'text-sm text-slate-500'}>
                    Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total ?? 0}{itemLabel ? ` ${itemLabel}` : ''}
                </p>
            )}
            <div className="flex flex-wrap items-center justify-end gap-2">
                <div className="flex flex-wrap items-center gap-1">
                    {hasPageButtons && links.map((link, index) => {
                    const label = link.label
                        .replace('&laquo; Previous', 'Previous')
                        .replace('Next &raquo;', 'Next');

                    const isPrev = index === 0;
                    const isNext = index === links.length - 1;

                    return (
                        <button
                            key={`${label}-${index}`}
                            type="button"
                            disabled={!link.url}
                            onClick={() => goTo(link.url)}
                            className={cn(
                                'inline-flex items-center justify-center font-medium transition',
                                compact
                                    ? 'min-h-6 rounded px-1.5 text-[7px]'
                                    : 'min-h-10 rounded-lg px-3 text-sm',
                                link.active
                                    ? 'bg-brand text-white'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                                !link.url && 'cursor-not-allowed opacity-40',
                            )}
                        >
                            {isPrev && <ChevronLeft className={compact ? 'mr-0.5 h-3 w-3' : 'mr-1 h-4 w-4'} />}
                            {label}
                            {isNext && <ChevronRight className={compact ? 'ml-0.5 h-3 w-3' : 'ml-1 h-4 w-4'} />}
                        </button>
                    );
                    })}
                </div>
                {perPageOptions.length > 0 && (
                    <select
                        value={perPage}
                        onChange={(event) => onPerPageChange?.(Number(event.target.value))}
                        className={cn(
                            'rounded border border-slate-200 bg-white text-slate-600 outline-none',
                            compact ? 'h-6 px-1.5 text-[7px]' : 'h-10 px-2 text-sm',
                        )}
                        aria-label="Items per page"
                    >
                        {perPageOptions.map((option) => (
                            <option key={option} value={option}>{option} per page</option>
                        ))}
                    </select>
                )}
            </div>
        </div>
    );
}
