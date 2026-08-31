import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/utils/helpers';

/**
 * The filter row shared by the List and Calendar views: dropdown filters on the
 * left, the window stepper in the middle, and a legend (or anything else the
 * view needs) on the right.
 *
 * `stepper` is 'compact' for the seven-day list window and 'labelled' for the
 * calendar, which spells out Previous/Next Week beside a two-week caption.
 */
export default function ScheduleFilterBar({
    selects = [],
    rangeLabel = '',
    rangeCaption = null,
    stepper = 'compact',
    onPrevious = () => {},
    onNext = () => {},
    onToday = () => {},
    children,
}) {
    const labelled = stepper === 'labelled';

    return (
        <div className="card flex flex-wrap items-center gap-2 px-3 py-2">
            {selects.map((select) => (
                <select
                    key={select.key}
                    value={select.value ?? 'all'}
                    onChange={(event) => select.onChange(event.target.value)}
                    aria-label={select.allLabel}
                    className="min-h-7 rounded-lg border-slate-200 py-1 pl-2 pr-7 text-[11px] text-slate-600 focus:border-brand focus:ring-brand"
                >
                    <option value="all">{select.allLabel}</option>
                    {select.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ))}

            <div className={cn('flex items-center gap-1.5', labelled ? 'ml-auto' : '')}>
                {labelled && (
                    <button type="button" onClick={onPrevious} className="btn-secondary min-h-7 px-2 py-1 text-[11px]">
                        <ChevronLeft className="h-3.5 w-3.5" />
                        Previous Week
                    </button>
                )}

                <div
                    className={cn(
                        'flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 py-1 text-[11px] text-slate-700',
                        labelled && 'flex-col gap-0 border-0 px-3 py-0 text-center',
                    )}
                >
                    {!labelled && <CalendarDays className="h-3.5 w-3.5 text-slate-400" />}
                    <span className="whitespace-nowrap font-medium">{rangeLabel}</span>
                    {rangeCaption && <span className="text-[10px] text-slate-400">{rangeCaption}</span>}

                    {!labelled && (
                        <>
                            <button
                                type="button"
                                onClick={onPrevious}
                                aria-label="Previous week"
                                className="rounded p-0.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <ChevronLeft className="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                onClick={onNext}
                                aria-label="Next week"
                                className="rounded p-0.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <ChevronRight className="h-3.5 w-3.5" />
                            </button>
                        </>
                    )}
                </div>

                {labelled && (
                    <button type="button" onClick={onNext} className="btn-secondary min-h-7 px-2 py-1 text-[11px]">
                        Next Week
                        <ChevronRight className="h-3.5 w-3.5" />
                    </button>
                )}

                <button type="button" onClick={onToday} className="btn-secondary min-h-7 px-2 py-1 text-[11px]">
                    <CalendarDays className={cn('h-3.5 w-3.5 text-slate-400', labelled && 'hidden')} />
                    Today
                </button>
            </div>

            {children && <div className="ml-auto flex flex-wrap items-center gap-3">{children}</div>}
        </div>
    );
}
