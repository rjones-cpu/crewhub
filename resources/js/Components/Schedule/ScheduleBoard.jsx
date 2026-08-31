import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, GripVertical, Smartphone } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { cn } from '@/utils/helpers';
import RoomNightsModal from './RoomNightsModal';
import { saveDrag, savePaint } from './scheduleApi';
import {
    MODE_SELECTION,
    rangeDates,
    resolveDrag,
    typeAt,
} from './scheduleDragRules';
import {
    DAY_FILL,
    DAY_TYPE_LABEL,
    FOOTER_ROW_HEIGHT,
    GRID_LINE,
    HEADER_HEIGHT,
    MIN_VISIBLE_ROWS,
    ROW_TINT,
    SCROLLBAR_ALLOWANCE,
    STICKY_COLUMNS,
    STICKY_WIDTH,
    stickyOffset,
} from './scheduleConstants';

const MENU_TYPES = [
    { type: 'work', label: 'Work', color: DAY_FILL.work },
    { type: 'travel', label: 'Travel', color: DAY_FILL.travel },
    { type: 'off', label: 'Off', color: '#ffffff' },
];

/**
 * Every day box carries its own hairline, so the grid stays visible through the blue
 * and yellow fills. Camp gives each box a full border; drawing only right/bottom keeps
 * the shared edges at 1px instead of doubling them.
 */
const CELL_BORDER = { borderRight: `1px solid ${GRID_LINE}`, borderBottom: `1px solid ${GRID_LINE}` };

/**
 * Camp row hover: the row lifts out of the grid behind a white gutter and casts a soft
 * shadow over its neighbours.
 */
const ROW_HOVER = 'hover:relative hover:z-20 hover:shadow-[0_0_0_4px_#ffffff,0_0_10px_10px_rgba(0,0,0,0.5)]';

/** Today's rails sit above the fill, so the black lines stay visible on work days. */
function TodayRails() {
    return (
        <>
            <span className="pointer-events-none absolute inset-y-0 left-0 w-0.5 bg-black" />
            <span className="pointer-events-none absolute inset-y-0 right-0 w-0.5 bg-black" />
        </>
    );
}

/**
 * Camp sizes day boxes in vw. `--schedule-cell` (app.css) holds that value, so the
 * grid resizes with the window without a resize listener.
 */
const CELL = 'var(--schedule-cell)';
const cells = (count) => `calc(${count} * ${CELL})`;

function SortIcon({ state }) {
    if (state === 'asc') {
        return <ArrowUp className="h-2.5 w-2.5 shrink-0 text-brand" />;
    }

    if (state === 'desc') {
        return <ArrowDown className="h-2.5 w-2.5 shrink-0 text-brand" />;
    }

    return <ArrowUpDown className="h-2.5 w-2.5 shrink-0 text-slate-300" />;
}

function AccommodationStatus({ accommodation }) {
    return (
        <span className="block min-w-0 leading-[1.05]">
            <span className="block truncate text-[9px] font-medium text-slate-700">
                {accommodation?.label || 'Not Booked'}
            </span>
            {accommodation?.reference && (
                <span className="block truncate text-[8px] text-slate-400">{accommodation.reference}</span>
            )}
        </span>
    );
}

function rowTypesFrom(row, days) {
    const types = {};

    days.forEach((day, index) => {
        types[day.date] = row.cells?.[index] || 'off';
    });

    return types;
}

function applyCells(row, days, cells, markPending = true) {
    const nextCells = [...(row.cells || [])];
    const nextPending = [...(row.pending || days.map(() => false))];
    const nextNeedsRoom = [...(row.needs_room || days.map(() => false))];

    days.forEach((day, index) => {
        if (!Object.hasOwn(cells, day.date)) {
            return;
        }

        nextCells[index] = cells[day.date];
        nextPending[index] = markPending;
        nextNeedsRoom[index] = cells[day.date] !== 'off';
    });

    return { ...row, cells: nextCells, pending: nextPending, needs_room: nextNeedsRoom };
}

function reloadBoard() {
    router.reload({ only: ['rows', 'totals', 'drafts', 'requests'], preserveScroll: true });
}

export default function ScheduleBoard({ days = [], rows = [], totals = {}, canEdit = false }) {
    const [sort, setSort] = useState({ key: null, direction: 'asc' });
    const [localRows, setLocalRows] = useState(rows);
    const [drag, setDrag] = useState(null);
    const [menu, setMenu] = useState(null);
    const [roomNights, setRoomNights] = useState(null);
    const snapshotRef = useRef(null);
    const boardRef = useRef(null);

    useEffect(() => {
        setLocalRows(rows);
    }, [rows]);

    useEffect(() => {
        const close = (event) => {
            if (event.key === 'Escape') {
                setMenu(null);
            }
        };

        const clickAway = (event) => {
            if (!event.target.closest('[data-schedule-menu]')) {
                setMenu(null);
            }
        };

        document.addEventListener('keydown', close);
        document.addEventListener('mousedown', clickAway);

        return () => {
            document.removeEventListener('keydown', close);
            document.removeEventListener('mousedown', clickAway);
        };
    }, []);

    const bodyHeight = cells(MIN_VISIBLE_ROWS);
    const boardHeight = `calc(${bodyHeight} + ${HEADER_HEIGHT + FOOTER_ROW_HEIGHT * 2 + SCROLLBAR_ALLOWANCE}px)`;
    const gridWidth = cells(days.length);

    const sortedRows = useMemo(() => {
        if (!sort.key) {
            return localRows;
        }

        const value = (row) =>
            sort.key === 'accommodation' ? (row.accommodation?.label ?? '') : (row[sort.key] ?? '');

        return [...localRows].sort((a, b) => {
            const compared = String(value(a)).localeCompare(String(value(b)), undefined, {
                sensitivity: 'base',
            });

            return sort.direction === 'asc' ? compared : -compared;
        });
    }, [localRows, sort]);

    const toggleSort = (key) => {
        setSort((current) =>
            current.key === key
                ? { key, direction: current.direction === 'asc' ? 'desc' : 'asc' }
                : { key, direction: 'asc' },
        );
    };

    const previewCells = (row) => {
        if (!drag || drag.workerId !== row.id || !drag.hoverDate) {
            return null;
        }

        const snapshot = snapshotRef.current;
        const backDragRevert = Boolean(
            snapshot &&
                snapshot.workerId === drag.workerId &&
                snapshot.sourceDate === drag.hoverDate &&
                snapshot.dropDate === drag.sourceDate,
        );
        const types = backDragRevert ? snapshot.originalTypes : drag.types;
        const outcome = resolveDrag(drag.sourceDate, drag.hoverDate, types, backDragRevert);

        if (outcome.mode === MODE_SELECTION) {
            const selected = {};
            rangeDates(drag.sourceDate, drag.hoverDate).forEach((date) => {
                selected[date] = typeAt(types, date);
            });

            return { cells: selected, selection: true };
        }

        return { cells: outcome.cells, selection: false };
    };

    const beginDrag = (row, date, event) => {
        if (!canEdit || event.button !== 0) {
            return;
        }

        event.preventDefault();

        // Touch and pen get an implicit pointer capture on the source cell, which
        // would stop every other cell from reporting pointerenter mid-drag.
        if (event.currentTarget.hasPointerCapture?.(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
        }

        setMenu(null);
        setDrag({
            workerId: row.id,
            projectId: row.project_id,
            sourceDate: date,
            hoverDate: date,
            types: rowTypesFrom(row, days),
        });
    };

    const hoverDrag = (row, date) => {
        if (!drag || drag.workerId !== row.id) {
            return;
        }

        setDrag((current) => (current ? { ...current, hoverDate: date } : current));
    };

    const finishDrag = async () => {
        if (!drag) {
            return;
        }

        const current = drag;
        setDrag(null);

        if (current.sourceDate === current.hoverDate) {
            return;
        }

        const row = localRows.find((item) => item.id === current.workerId);

        if (!row) {
            return;
        }

        const snapshot = snapshotRef.current;
        const backDragRevert = Boolean(
            snapshot &&
                snapshot.workerId === current.workerId &&
                snapshot.sourceDate === current.hoverDate &&
                snapshot.dropDate === current.sourceDate,
        );
        const types = backDragRevert ? snapshot.originalTypes : current.types;
        const outcome = resolveDrag(current.sourceDate, current.hoverDate, types, backDragRevert);

        if (outcome.mode === MODE_SELECTION) {
            const dates = rangeDates(current.sourceDate, current.hoverDate);
            setMenu({
                x: 0,
                y: 0,
                usePointer: false,
                workerId: row.id,
                projectId: row.project_id,
                dates,
                whiteSelect: true,
            });
            setLocalRows((items) =>
                items.map((item) =>
                    item.id === row.id
                        ? applyCells(
                              item,
                              days,
                              Object.fromEntries(dates.map((date) => [date, typeAt(types, date)])),
                              true,
                          )
                        : item,
                ),
            );

            return;
        }

        snapshotRef.current = {
            workerId: current.workerId,
            sourceDate: current.sourceDate,
            dropDate: current.hoverDate,
            originalTypes: current.types,
        };

        setLocalRows((items) =>
            items.map((item) => (item.id === row.id ? applyCells(item, days, outcome.cells) : item)),
        );

        try {
            await saveDrag({
                worker_id: row.id,
                project_id: row.project_id,
                source_date: current.sourceDate,
                drop_date: current.hoverDate,
                row_types: types,
                back_drag_revert: backDragRevert,
            });
            reloadBoard();
        } catch (error) {
            reloadBoard();
            window.alert(error.message);
        }
    };

    useEffect(() => {
        if (!drag) {
            return undefined;
        }

        const finish = () => finishDrag();
        document.addEventListener('pointerup', finish);
        document.addEventListener('pointercancel', finish);

        return () => {
            document.removeEventListener('pointerup', finish);
            document.removeEventListener('pointercancel', finish);
        };
    }, [drag]);

    const openMenu = (row, date, event) => {
        if (!canEdit) {
            return;
        }

        event.preventDefault();
        setDrag(null);

        const pendingDates = days
            .filter((_, index) => row.pending?.[index])
            .map((day) => day.date);
        const useRange = menu?.whiteSelect && menu.workerId === row.id && menu.dates.includes(date);
        const dates = useRange ? menu.dates : pendingDates.length > 1 && pendingDates.includes(date) ? pendingDates : [date];

        setMenu({
            x: event.clientX,
            y: event.clientY,
            usePointer: true,
            workerId: row.id,
            projectId: row.project_id,
            dates,
            whiteSelect: Boolean(useRange),
        });
    };

    const applyMenuType = async (type) => {
        if (!menu) {
            return;
        }

        const { workerId, projectId, dates } = menu;
        setMenu(null);

        const cells = Object.fromEntries(dates.map((date) => [date, type]));
        setLocalRows((items) =>
            items.map((item) => (item.id === workerId ? applyCells(item, days, cells) : item)),
        );

        try {
            await savePaint({
                worker_id: workerId,
                project_id: projectId,
                dates,
                type,
            });

            if (type === 'travel' && dates.length >= 3) {
                setRoomNights({ workerId, projectId, dates, selected: [...dates] });
            } else {
                reloadBoard();
            }
        } catch (error) {
            reloadBoard();
            window.alert(error.message);
        }
    };

    const confirmRoomNights = async () => {
        if (!roomNights) {
            return;
        }

        const needsRoom = {};
        roomNights.dates.forEach((date) => {
            needsRoom[date] = roomNights.selected.includes(date);
        });

        try {
            await savePaint({
                worker_id: roomNights.workerId,
                project_id: roomNights.projectId,
                dates: roomNights.dates,
                type: 'travel',
                needs_room: needsRoom,
            });
        } catch (error) {
            window.alert(error.message);
        }

        setRoomNights(null);
        reloadBoard();
    };

    const totalRows = [
        { label: 'Total in Lodge', values: totals.in_lodge || [] },
        { label: 'Total Project Personnel', values: totals.project_personnel || [] },
    ];

    const fillerRows = Math.max(0, MIN_VISIBLE_ROWS - sortedRows.length);

    return (
        <div
            ref={boardRef}
            className="schedule-board overflow-hidden rounded-md border border-brand bg-white shadow-sm"
        >
            <div className="overflow-auto" style={{ height: boardHeight }}>
                <table
                    className="table-fixed border-separate border-spacing-0 select-none"
                    style={{ width: `calc(${STICKY_WIDTH}px + ${gridWidth})` }}
                >
                    <colgroup>
                        {STICKY_COLUMNS.map((column) => (
                            <col key={column.key} style={{ width: column.width }} />
                        ))}
                        <col style={{ width: gridWidth }} />
                    </colgroup>

                    <thead>
                        <tr style={{ height: HEADER_HEIGHT }}>
                            {STICKY_COLUMNS.map((column, index) => {
                                const state = sort.key === column.key ? sort.direction : null;

                                return (
                                    <th
                                        key={column.key}
                                        style={{ left: stickyOffset(index) }}
                                        className={cn(
                                            'sticky top-0 z-40 border-b border-slate-300 bg-white px-1 text-left align-middle',
                                            index === STICKY_COLUMNS.length - 1 && 'border-r',
                                        )}
                                    >
                                        <button
                                            type="button"
                                            onClick={() => toggleSort(column.key)}
                                            className="flex w-full items-center gap-0.5 text-left text-[8px] font-semibold uppercase leading-tight tracking-wide text-slate-500 transition hover:text-slate-900"
                                        >
                                            <span className="min-w-0 flex-1">{column.label}</span>
                                            <SortIcon state={state} />
                                        </button>
                                    </th>
                                );
                            })}

                            <th className="sticky top-0 z-30 border-b border-slate-300 bg-white p-0">
                                <div className="flex">
                                    {days.map((day) => (
                                        <div
                                            key={day.date}
                                            style={{ width: CELL, height: HEADER_HEIGHT }}
                                            className={cn(
                                                'flex shrink-0 flex-col items-center pt-[3px] leading-none',
                                                day.is_today && 'border-x border-black bg-black',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'text-[9px] font-bold uppercase leading-[1.1]',
                                                    day.is_today ? 'text-white' : 'text-slate-900',
                                                )}
                                            >
                                                {day.weekday}
                                            </span>
                                            <span
                                                className={cn(
                                                    'mt-[3px] text-[9px] uppercase leading-[1.1] underline',
                                                    day.is_today ? 'text-white' : 'text-slate-500',
                                                )}
                                            >
                                                {day.month}
                                            </span>
                                            <span
                                                className={cn(
                                                    'mt-[5px] text-[10px] font-bold leading-none',
                                                    day.is_today ? 'text-white' : 'text-slate-900',
                                                )}
                                            >
                                                {day.day}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {sortedRows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={STICKY_COLUMNS.length + 1}
                                    style={{ height: bodyHeight }}
                                    className="px-4 text-center align-middle text-xs text-slate-500"
                                >
                                    No workers are scheduled on this project yet.
                                </td>
                            </tr>
                        )}

                        {sortedRows.map((row, rowIndex) => {
                            const preview = previewCells(row);
                            // Camp zebras the day grid only — the frozen label columns stay white.
                            const rowTint = rowIndex % 2 === 0 ? '#ffffff' : ROW_TINT;

                            return (
                                <tr key={row.id} className={cn('group', ROW_HOVER)}>
                                    <td
                                        style={{ left: stickyOffset(0), height: CELL }}
                                        className="sticky z-10 border-b border-slate-200 bg-white px-1"
                                    >
                                        <div className="flex min-w-0 items-center gap-0.5">
                                            <GripVertical className="h-3 w-3 shrink-0 text-slate-300" />
                                            <span className="truncate text-[9px] font-medium text-slate-700">
                                                {row.last_name}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        style={{ left: stickyOffset(1) }}
                                        className="sticky z-10 border-b border-l border-slate-200 bg-white px-1 text-[9px] text-slate-600"
                                    >
                                        <span className="block truncate">{row.first_name}</span>
                                    </td>
                                    <td
                                        style={{ left: stickyOffset(2) }}
                                        className="sticky z-10 border-b border-l border-slate-200 bg-white px-1"
                                        title={row.company || undefined}
                                    >
                                        <span className="block truncate text-[9px] leading-tight text-slate-600">
                                            {row.company || '—'}
                                        </span>
                                    </td>
                                    <td
                                        style={{ left: stickyOffset(3) }}
                                        className="sticky z-10 border-b border-l border-slate-200 bg-white px-0.5 text-center"
                                        title={row.app_status === 'connected' ? 'App connected' : 'App not connected'}
                                    >
                                        <Smartphone
                                            className={cn(
                                                'mx-auto h-3 w-3',
                                                row.app_status === 'connected' ? 'text-success' : 'text-slate-400',
                                            )}
                                        />
                                    </td>
                                    <td
                                        style={{ left: stickyOffset(4) }}
                                        className="sticky z-10 border-b border-l border-r border-slate-200 bg-white px-1"
                                    >
                                        <AccommodationStatus accommodation={row.accommodation} />
                                    </td>

                                    <td className="p-0" style={{ backgroundColor: rowTint }}>
                                        <div className="flex" style={{ height: CELL }}>
                                            {days.map((day, index) => {
                                                const previewType = preview?.cells?.[day.date];
                                                const type = previewType || row.cells?.[index] || 'off';
                                                const fill = DAY_FILL[type];
                                                const pending = row.pending?.[index];
                                                // Only the grabbed box and the box under the cursor get a marker;
                                                // the rest of the range simply repaints, like the camp board.
                                                const dragging = drag?.workerId === row.id;
                                                const grabbed = dragging && drag.sourceDate === day.date;
                                                const landing = dragging && drag.hoverDate === day.date && !grabbed;

                                                return (
                                                    <div
                                                        key={day.date}
                                                        // The fill sits on the box itself, not an inner div, so the
                                                        // hairline blends into it the way Camp's bordered spans do.
                                                        style={{ width: CELL, backgroundColor: fill, ...CELL_BORDER }}
                                                        title={`${row.full_name} — ${day.label} — ${DAY_TYPE_LABEL[type]}${canEdit ? ' (drag or right-click)' : ''}`}
                                                        onPointerDown={(event) => beginDrag(row, day.date, event)}
                                                        onPointerEnter={() => hoverDrag(row, day.date)}
                                                        onContextMenu={(event) => openMenu(row, day.date, event)}
                                                        data-status={type}
                                                        aria-label={`${row.full_name}, ${day.label}, ${DAY_TYPE_LABEL[type]}`}
                                                        className={cn(
                                                            'relative h-full shrink-0 touch-none',
                                                            canEdit && type === 'travel' && 'cursor-grab active:cursor-grabbing',
                                                            canEdit && type === 'work' && 'cursor-ew-resize',
                                                            canEdit && type === 'off' && 'cursor-cell',
                                                            pending && 'z-[1] outline outline-1 -outline-offset-1 outline-slate-900',
                                                            landing && 'z-[2] ring-1 ring-inset ring-slate-900',
                                                            grabbed && 'z-[2] ring-2 ring-inset ring-slate-900',
                                                        )}
                                                    >
                                                        {day.is_today && <TodayRails />}
                                                        {row.needs_room?.[index] && type === 'travel' && (
                                                            <span className="pointer-events-none absolute inset-0 flex items-center justify-center text-[8px] leading-none text-black/40">
                                                                ▢
                                                            </span>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}

                        {sortedRows.length > 0 &&
                            Array.from({ length: fillerRows }, (_, index) => (
                                <tr key={`filler-${index}`} style={{ height: CELL }}>
                                    {STICKY_COLUMNS.map((column, columnIndex) => (
                                        <td
                                            key={column.key}
                                            style={{ left: stickyOffset(columnIndex) }}
                                            className={cn(
                                                'sticky z-10 border-b border-slate-200 bg-white',
                                                columnIndex === STICKY_COLUMNS.length - 1 && 'border-r',
                                            )}
                                        />
                                    ))}
                                    <td
                                        className="p-0"
                                        style={{
                                            backgroundColor:
                                                (sortedRows.length + index) % 2 === 0 ? '#ffffff' : ROW_TINT,
                                        }}
                                    >
                                        <div className="flex" style={{ height: CELL }}>
                                            {days.map((day) => (
                                                <div
                                                    key={day.date}
                                                    style={{ width: CELL, ...CELL_BORDER }}
                                                    className="relative h-full shrink-0"
                                                >
                                                    {day.is_today && <TodayRails />}
                                                </div>
                                            ))}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                    </tbody>

                    <tfoot>
                        {totalRows.map((total, rowIndex) => {
                            const bottom = rowIndex === 0 ? FOOTER_ROW_HEIGHT : 0;

                            return (
                                <tr key={total.label} style={{ height: FOOTER_ROW_HEIGHT }}>
                                    <td
                                        colSpan={STICKY_COLUMNS.length}
                                        style={{ bottom, left: 0 }}
                                        className={cn(
                                            'sticky z-40 border-r border-slate-300 bg-white px-1.5 text-[9px] font-bold uppercase tracking-wide text-slate-700',
                                            rowIndex === 0
                                                ? 'border-t-2 border-t-slate-300'
                                                : 'border-t border-t-slate-200',
                                        )}
                                    >
                                        {total.label}
                                    </td>
                                    <td
                                        style={{ bottom }}
                                        className={cn(
                                            'sticky z-30 bg-white p-0',
                                            rowIndex === 0
                                                ? 'border-t-2 border-t-slate-300'
                                                : 'border-t border-t-slate-200',
                                        )}
                                    >
                                        <div className="flex">
                                            {days.map((day, index) => (
                                                <div
                                                    key={day.date}
                                                    style={{
                                                        width: CELL,
                                                        height: FOOTER_ROW_HEIGHT,
                                                        borderRight: `1px solid ${GRID_LINE}`,
                                                    }}
                                                    className={cn(
                                                        'flex shrink-0 items-center justify-center text-[9px] font-semibold',
                                                        day.is_today
                                                            ? 'border-x border-black bg-black text-white'
                                                            : 'text-slate-600',
                                                    )}
                                                >
                                                    {total.values[index] ?? 0}
                                                </div>
                                            ))}
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tfoot>
                </table>
            </div>

            {menu?.usePointer && (
                <div
                    data-schedule-menu
                    className="fixed z-50 min-w-[140px] rounded-md border border-slate-200 bg-white py-1 shadow-lg"
                    style={{ left: menu.x, top: menu.y }}
                >
                    {MENU_TYPES.map((option) => (
                        <button
                            key={option.type}
                            type="button"
                            onClick={() => applyMenuType(option.type)}
                            className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs text-slate-700 hover:bg-slate-50"
                        >
                            <span
                                className="h-3 w-3 rounded-sm border border-slate-200"
                                style={{ backgroundColor: option.color }}
                            />
                            {option.label}
                            {menu.dates.length > 1 && (
                                <span className="ml-auto text-[10px] text-slate-400">{menu.dates.length}</span>
                            )}
                        </button>
                    ))}
                </div>
            )}

            {menu && !menu.usePointer && (
                <p className="border-t border-slate-100 px-3 py-1.5 text-[11px] text-slate-500">
                    Off-day range selected. Right-click the highlighted cells to paint Work, Travel, or Off.
                </p>
            )}

            {roomNights && (
                <RoomNightsModal
                    dates={roomNights.dates}
                    selected={roomNights.selected}
                    onToggle={(date) =>
                        setRoomNights((current) => ({
                            ...current,
                            selected: current.selected.includes(date)
                                ? current.selected.filter((item) => item !== date)
                                : [...current.selected, date],
                        }))
                    }
                    onConfirm={confirmRoomNights}
                    onClose={() => {
                        setRoomNights(null);
                        reloadBoard();
                    }}
                />
            )}
        </div>
    );
}
