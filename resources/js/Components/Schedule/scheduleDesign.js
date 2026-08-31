import {
    ArrowLeftRight,
    CalendarOff,
    CalendarX2,
    CircleCheck,
    CircleX,
    Clock,
    ClockAlert,
    PencilLine,
    PlaneLanding,
    PlaneTakeoff,
    Repeat2,
    Timer,
    TriangleAlert,
    UserPlus,
    Users,
} from 'lucide-react';

/**
 * One source of truth for how the Schedule views colour shifts, coverage
 * verdicts, and request states. The list, calendar, and change-request screens
 * all read from here so a Night Shift is the same violet everywhere.
 */

/** Day-cell states, in legend order. */
export const SHIFT_STATUS = {
    day: {
        label: 'Day Shift',
        dot: 'bg-brand',
        text: 'text-brand',
        cell: 'bg-brand-soft/40',
        soft: 'bg-brand-soft text-brand',
    },
    night: {
        label: 'Night Shift',
        dot: 'bg-violet-500',
        text: 'text-violet-600',
        cell: 'bg-violet-50/50',
        soft: 'bg-violet-50 text-violet-700',
    },
    on_call: {
        label: 'On Call / Rotational',
        dot: 'bg-success',
        text: 'text-orange-600',
        cell: 'bg-orange-50/70',
        soft: 'bg-orange-50 text-orange-700',
    },
    booked_off: {
        label: 'Booked Off',
        dot: 'bg-rose-400',
        text: 'text-purple-700',
        cell: 'bg-purple-50',
        soft: 'bg-purple-50 text-purple-700',
    },
    unavailable: {
        label: 'Unavailable',
        dot: 'bg-slate-300',
        text: 'text-danger',
        cell: 'bg-danger-soft',
        soft: 'bg-danger-soft text-danger',
    },
    off: {
        label: 'Off',
        dot: 'bg-slate-200',
        text: 'text-slate-500',
        cell: 'bg-slate-50',
        soft: 'bg-slate-100 text-slate-500',
    },
};

/** Legend under the list filter bar. */
export const LIST_LEGEND = ['day', 'night', 'on_call', 'booked_off', 'unavailable'];

/** Coverage verdicts on the calendar's third row. */
export const COVERAGE_TONE = {
    good: {
        label: 'Good',
        className: 'text-success',
        icon: CircleCheck,
    },
    gap: {
        label: 'Coverage Gap',
        className: 'rounded-md border border-dashed border-danger bg-danger-soft px-2 py-0.5 text-danger',
        icon: null,
    },
    overtime: {
        label: 'Overtime',
        className: 'rounded-md bg-amber-50 px-2 py-0.5 text-amber-700',
        icon: Clock,
    },
    departures: {
        label: 'Departures',
        className: 'rounded-md border border-violet-300 px-2 py-0.5 text-violet-600',
        icon: null,
    },
};

/** Calendar legend swatches, including the states the grid only hints at. */
export const CALENDAR_LEGEND = [
    { key: 'day', label: 'Day Shift', swatch: 'dot', className: 'bg-brand' },
    { key: 'night', label: 'Night Shift', swatch: 'dot', className: 'bg-violet-500' },
    { key: 'on_shift', label: 'On Shift / Rotational', swatch: 'dot', className: 'bg-success' },
    { key: 'booked_off', label: 'Booked Off', swatch: 'dot', className: 'bg-rose-400' },
    { key: 'unavailable', label: 'Unavailable', swatch: 'dot', className: 'bg-slate-300' },
    {
        key: 'open_shift',
        label: 'Open Shift',
        swatch: 'square',
        className: 'border border-dashed border-brand bg-brand-soft',
    },
    {
        key: 'coverage_gap',
        label: 'Coverage Gap',
        swatch: 'square',
        className: 'border border-dashed border-danger bg-danger-soft',
    },
    { key: 'overtime', label: 'Overtime', swatch: 'square', className: 'bg-amber-400' },
];

/** Change-request types: badge icon and tint. */
export const REQUEST_TYPE = {
    shift_swap: { label: 'Shift Swap', icon: ArrowLeftRight, className: 'bg-brand-soft text-brand' },
    day_off: { label: 'Day Off', icon: CalendarOff, className: 'bg-slate-100 text-slate-600' },
    sick_replacement: { label: 'Sick Replacement', icon: UserPlus, className: 'bg-rose-50 text-rose-600' },
    overtime_extension: { label: 'Overtime Extension', icon: Timer, className: 'bg-amber-50 text-amber-700' },
    late_arrival: { label: 'Late Arrival', icon: ClockAlert, className: 'bg-orange-50 text-orange-700' },
    reassignment: { label: 'Reassignment', icon: Repeat2, className: 'bg-violet-50 text-violet-700' },
    schedule_correction: { label: 'Schedule Correction', icon: PencilLine, className: 'bg-teal-50 text-teal-700' },
};

/** Change-request statuses. */
export const REQUEST_STATUS = {
    pending: { label: 'Pending Approval', className: 'bg-amber-50 text-amber-700' },
    overtime_pending: { label: 'Overtime Pending Approval', className: 'bg-orange-50 text-orange-700' },
    approved: { label: 'Approved', className: 'bg-success-soft text-success' },
    rejected: { label: 'Rejected', className: 'bg-danger-soft text-danger' },
};

/** Coverage impact of a single request. */
export const IMPACT_TONE = {
    low: { label: 'Low', dot: 'bg-success', text: 'text-success', soft: 'bg-success-soft text-success' },
    medium: { label: 'Medium', dot: 'bg-amber-500', text: 'text-amber-700', soft: 'bg-amber-50 text-amber-700' },
    high: { label: 'High', dot: 'bg-danger', text: 'text-danger', soft: 'bg-danger-soft text-danger' },
};

/** Approval chain step states. */
export const APPROVAL_STATE = {
    approved: { label: 'Approved', className: 'text-success' },
    pending: { label: 'Pending', className: 'text-amber-600' },
    waiting: { label: 'Waiting', className: 'text-slate-400' },
    rejected: { label: 'Rejected', className: 'text-danger' },
    not_required: { label: 'Not Required', className: 'text-slate-400' },
};

/** Tones shared by both KPI strips. */
export const KPI_TONE = {
    brand: 'bg-brand-soft text-brand',
    success: 'bg-success-soft text-success',
    warning: 'bg-amber-50 text-amber-600',
    danger: 'bg-danger-soft text-danger',
    journey: 'bg-journey-soft text-journey',
};

/** Icons the server may name on a KPI card. */
export const KPI_ICONS = {
    Users,
    Clock,
    Timer,
    PlaneLanding,
    PlaneTakeoff,
    TriangleAlert,
    CircleCheck,
    CircleX,
    CalendarX2,
};

export const shiftStatus = (status) => SHIFT_STATUS[status] || SHIFT_STATUS.off;

export const requestType = (type) => REQUEST_TYPE[type] || REQUEST_TYPE.schedule_correction;

export const requestStatus = (status) => REQUEST_STATUS[status] || REQUEST_STATUS.pending;

export const impactTone = (impact) => IMPACT_TONE[impact] || IMPACT_TONE.low;
