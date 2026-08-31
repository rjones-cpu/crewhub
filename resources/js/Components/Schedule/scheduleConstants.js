import { Home, LogOut, Minus, PlaneLanding } from 'lucide-react';

/**
 * Grid geometry ported from the Camp board (`.travel_day_box`): day boxes are squares
 * sized off the viewport — 2.2vw, dropping to 1.9vw under 1500px — and the row height
 * is the same value, so a rotation reads identically at any window width. The size
 * itself lives in `--schedule-cell` (app.css) so CSS handles the breakpoint.
 */
/** Camp `.travel_days_th` min-height; the date strip is three centred lines. */
export const HEADER_HEIGHT = 44;
export const FOOTER_ROW_HEIGHT = 24;

/**
 * The board keeps a fixed body height so it never collapses on projects with only a
 * few workers: short boards are padded with empty grid rows, longer ones scroll.
 */
export const MIN_VISIBLE_ROWS = 12;

/** Room for the always-present horizontal scrollbar, so no vertical one appears. */
export const SCROLLBAR_ALLOWANCE = 18;

/** Camp day-box hairline (`border: 1px solid #0000001A`) and day-grid zebra tint. */
export const GRID_LINE = 'rgba(0, 0, 0, 0.1)';
export const ROW_TINT = '#f7faff';

/** Frozen left columns, in render order. Widths drive the sticky offsets. */
export const STICKY_COLUMNS = [
    { key: 'last_name', label: 'Last Name', width: 92 },
    { key: 'first_name', label: 'First Name', width: 76 },
    { key: 'company', label: 'Company', width: 116 },
    { key: 'app_status', label: 'App', width: 38 },
    { key: 'accommodation', label: 'Status', width: 112 },
];

export const STICKY_WIDTH = STICKY_COLUMNS.reduce((total, column) => total + column.width, 0);

/** Cumulative left offset for each frozen column. */
export const stickyOffset = (index) =>
    STICKY_COLUMNS.slice(0, index).reduce((total, column) => total + column.width, 0);

/** Day-cell fill per schedule day type, straight from Camp. Off days stay empty. */
export const DAY_FILL = {
    work: '#2f6fed',
    travel: '#f9d34a',
    off: null,
};

export const DAY_TYPE_LABEL = {
    work: 'Work',
    travel: 'Travel',
    off: 'Off',
};

export const ACCOMMODATION_STATUS = {
    in_house: { label: 'In House', icon: Home, className: 'bg-success-soft text-success' },
    arriving: { label: 'Arriving', icon: PlaneLanding, className: 'bg-brand-soft text-brand' },
    check_out: { label: 'Check Out', icon: LogOut, className: 'bg-warning-soft text-warning' },
    not_booked: { label: 'Not Booked', icon: Minus, className: 'bg-slate-100 text-slate-500' },
};

/** Projects shown as chips before the rest collapse into More Projects. */
export const VISIBLE_PROJECT_CHIPS = 7;
