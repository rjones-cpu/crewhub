import { CalendarCheck2, PanelRightOpen } from 'lucide-react';
import { cn } from '@/utils/helpers';
import { CALENDAR_LEGEND, COVERAGE_TONE, SHIFT_STATUS } from './scheduleDesign';

/** Shift rows of the grid, in screen order. Hours are fixed lodge shift windows. */
const SHIFT_ROWS = [
    { key: 'day', hours: '7a – 3p' },
    { key: 'night', hours: '3p – 11p' },
];

/** Narrow row-label column followed by seven equal day columns. */
const GRID_COLUMNS = '86px repeat(7, minmax(0, 1fr))';

function LegendStrip({ className = '' }) {
    return (
        <div className={cn('flex flex-wrap items-center gap-x-5 gap-y-1.5', className)}>
            {CALENDAR_LEGEND.map((item) => (
                <span key={item.key} className="flex items-center gap-1.5 text-[10px] text-slate-500">
                    <span
                        className={cn(
                            item.swatch === 'dot' ? 'h-1.5 w-1.5 rounded-full' : 'h-2 w-3 rounded-sm',
                            item.className,
                        )}
                    />
                    {item.label}
                </span>
            ))}
        </div>
    );
}

/**
 * Booked time off replaces the coverage numbers on at most one day shift per
 * week — the day carrying the most absences, and only when it actually costs
 * the week some cover. Anything looser would hide the numbers across the row.
 */
function bookedOffDate(days) {
    const candidates = days.filter(
        (day) => (day?.booked_off || 0) > 0 && (day?.shifts?.day?.percent ?? 100) < 100,
    );

    if (candidates.length === 0) {
        return null;
    }

    return candidates.reduce((worst, day) => (day.booked_off > worst.booked_off ? day : worst)).date;
}

function ShiftCell({ shift, shiftKey }) {
    const scheduled = shift?.scheduled ?? 0;
    const required = shift?.required ?? 0;
    const percent = shift?.percent ?? 0;

    if (percent === 0 && required === 0) {
        return (
            <div className="rounded-md border border-slate-100 bg-slate-50/60 px-1.5 py-1 text-center text-[10px] text-slate-300">
                —
            </div>
        );
    }

    return (
        <div className="flex items-center justify-between gap-1 rounded-md border border-slate-100 bg-slate-50/60 px-1.5 py-1">
            <span className={cn('text-[10px] font-semibold', SHIFT_STATUS[shiftKey]?.text)}>{percent}%</span>
            <span className="text-[10px] text-slate-500">
                {scheduled} / {required}
            </span>
        </div>
    );
}

function CoverageCell({ coverage }) {
    const verdict = COVERAGE_TONE[coverage?.tone] || COVERAGE_TONE.good;
    const Icon = verdict.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-[10px] font-medium leading-tight',
                verdict.className,
            )}
        >
            {Icon && <Icon className="h-3 w-3 shrink-0" />}
            {coverage?.label || verdict.label}
        </span>
    );
}

export default function ScheduleCalendarView({ calendar = {}, railHidden = false, onOpenRail = () => {} }) {
    const weeks = calendar?.weeks || [];

    return (
        <div className="space-y-2">
            <LegendStrip className="px-1" />

            <div className="card">
                {railHidden && (
                    <div className="flex items-center justify-end px-3 pt-2">
                        <button
                            type="button"
                            onClick={onOpenRail}
                            title="Show schedule context panel"
                            className="rounded-md p-1 text-slate-400 transition hover:bg-slate-50 hover:text-slate-600"
                        >
                            <PanelRightOpen className="h-3.5 w-3.5" />
                        </button>
                    </div>
                )}

                {weeks.length === 0 ? (
                    <p className="px-4 pb-4 text-center text-xs text-slate-500">
                        No schedule data for this period.
                    </p>
                ) : (
                    weeks.map((week, weekIndex) => {
                        const days = week?.days || [];
                        const bookedOff = bookedOffDate(days);

                        return (
                            <div
                                key={days[0]?.date || weekIndex}
                                className={cn('grid', weekIndex > 0 && 'border-t border-slate-200')}
                                style={{ gridTemplateColumns: GRID_COLUMNS }}
                            >
                                <div />
                                {days.map((day) => (
                                    <div
                                        key={`head-${day.date}`}
                                        className="flex flex-col items-center justify-center px-1 py-2.5 text-center"
                                    >
                                        <span
                                            className={cn(
                                                'text-[10px] font-semibold',
                                                day.is_today ? 'text-brand' : 'text-slate-700',
                                            )}
                                        >
                                            {day.weekday}
                                        </span>
                                        <span
                                            className={cn(
                                                'text-[11px] font-bold',
                                                day.is_today
                                                    ? 'rounded bg-brand-soft px-1.5 text-brand'
                                                    : 'text-slate-800',
                                            )}
                                        >
                                            {day.day_label}
                                        </span>
                                    </div>
                                ))}

                                {SHIFT_ROWS.map((row) => {
                                    const tone = SHIFT_STATUS[row.key];

                                    return (
                                        <div key={`${weekIndex}-${row.key}`} className="contents">
                                            <div className="flex flex-col justify-center border-t border-slate-100 px-2 py-2">
                                                <span className="flex items-center gap-1.5 text-[10px] font-semibold text-slate-700">
                                                    <span className={cn('h-1.5 w-1.5 shrink-0 rounded-full', tone.dot)} />
                                                    {tone.label}
                                                </span>
                                                <span className="pl-3 text-[10px] text-slate-400">{row.hours}</span>
                                            </div>

                                            {days.map((day) => (
                                                <div
                                                    key={`${row.key}-${day.date}`}
                                                    className="flex items-center border-l border-t border-slate-100 px-1.5 py-2"
                                                >
                                                    {row.key === 'day' && day.date === bookedOff ? (
                                                        <span className="w-full rounded-md bg-rose-50 px-2 py-1 text-center text-[10px] font-medium text-rose-600">
                                                            Booked Off
                                                        </span>
                                                    ) : (
                                                        <div className="w-full">
                                                            <ShiftCell shift={day.shifts?.[row.key]} shiftKey={row.key} />
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    );
                                })}

                                <div className="flex items-center gap-1.5 border-t border-slate-100 px-2 py-2 text-[10px] font-medium text-slate-500">
                                    <CalendarCheck2 className="h-3 w-3 shrink-0 text-slate-400" />
                                    Coverage
                                </div>
                                {days.map((day) => (
                                    <div
                                        key={`coverage-${day.date}`}
                                        className="flex items-center justify-center border-l border-t border-slate-100 px-1.5 py-2 text-center"
                                    >
                                        <CoverageCell coverage={day.coverage} />
                                    </div>
                                ))}
                            </div>
                        );
                    })
                )}

                <div className="flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-slate-200 px-3 py-2">
                    <span className="text-[10px] font-medium text-slate-500">Legend:</span>
                    <LegendStrip />
                </div>
            </div>
        </div>
    );
}
