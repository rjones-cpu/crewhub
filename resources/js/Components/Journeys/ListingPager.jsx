import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/utils/helpers';

export const PER_PAGE_OPTIONS = [10, 25, 50, 100];

export default function ListingPager({ links = [], onNavigate, perPage, onPerPageChange }) {
    return (
        <div className="flex items-center gap-2">
            <div className="flex items-center gap-1">
                {links.map((link, index) => {
                    const isPrev = index === 0;
                    const isNext = index === links.length - 1;
                    const label = link.label
                        .replace('&laquo; Previous', '')
                        .replace('Next &raquo;', '');

                    return (
                        <button
                            key={`${link.label}-${index}`}
                            type="button"
                            disabled={!link.url}
                            onClick={() => onNavigate(link.url)}
                            aria-label={isPrev ? 'Previous page' : isNext ? 'Next page' : `Page ${label}`}
                            className={cn(
                                'inline-flex h-7 min-w-[28px] items-center justify-center rounded-md border px-2 text-[11px] font-medium transition',
                                link.active
                                    ? 'border-brand bg-brand text-white'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                !link.url && 'cursor-not-allowed opacity-40',
                            )}
                        >
                            {isPrev ? (
                                <ChevronLeft className="h-3.5 w-3.5" />
                            ) : isNext ? (
                                <ChevronRight className="h-3.5 w-3.5" />
                            ) : (
                                label
                            )}
                        </button>
                    );
                })}
            </div>
            <select
                value={perPage}
                onChange={(event) => onPerPageChange(Number(event.target.value))}
                aria-label="Rows per page"
                className="h-7 rounded-md border-slate-200 py-0 pl-2.5 pr-7 text-[11px] text-slate-600 focus:border-brand focus:ring-brand"
            >
                {PER_PAGE_OPTIONS.map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
        </div>
    );
}
