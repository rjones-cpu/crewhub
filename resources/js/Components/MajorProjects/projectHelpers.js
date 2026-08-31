import { MAJOR_PROJECT_TABS } from '@/utils/constants';
import { statusLabel } from '@/utils/formatters';

/**
 * Role-gated Major Projects hub tabs.
 * Create is shown when the user may attempt creation (form or locked notice).
 * Join is for CrewHub company users.
 */
export function majorProjectTabs({ canAttemptCreate = false, canJoin = false } = {}) {
    return MAJOR_PROJECT_TABS.filter((tab) => {
        if (tab.route === 'major-projects.create') {
            return canAttemptCreate;
        }
        if (tab.route === 'major-projects.join') {
            return canJoin;
        }
        return true;
    });
}

export const PROJECT_MODULE_OPTIONS = [
    { key: 'schedule', label: 'Schedule', icon: 'Calendar' },
    { key: 'timesheets', label: 'Timesheets', icon: 'Clock' },
    { key: 'accommodations', label: 'Accommodations', icon: 'BedDouble' },
    { key: 'journey_management', label: 'Journey Management', icon: 'Plane' },
    { key: 'lms', label: 'LMS', icon: 'GraduationCap' },
];

export const INVITATION_STATUS_OPTIONS = [
    { value: 'pending', label: 'Invited' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'declined', label: 'Declined' },
];

const INVITATION_STATUS_TONES = {
    pending: 'warning',
    accepted: 'success',
    declined: 'danger',
};

export function invitationStatusValue(status) {
    return status?.value ?? status ?? '';
}

export function invitationStatusLabel(status) {
    const value = invitationStatusValue(status);

    return INVITATION_STATUS_OPTIONS.find((option) => option.value === value)?.label
        || statusLabel(value);
}

export function invitationStatusTone(status) {
    return INVITATION_STATUS_TONES[invitationStatusValue(status)] || 'slate';
}

/** Values of ProjectStatus, in the order the listing filter offers them. */
export const PROJECT_STATUS_OPTIONS = ['active', 'planned', 'completed', 'archived'];

// `on_hold` is not a ProjectStatus case yet, but Camp-synced rows can carry it,
// so it is mapped here rather than falling back to the generic label.
const STATUS_LABELS = {
    planned: 'Planning',
    on_hold: 'On Hold',
};

const STATUS_DOT_CLASSES = {
    active: 'bg-success',
    planned: 'bg-amber-500',
    on_hold: 'bg-brand',
    completed: 'bg-slate-400',
    archived: 'bg-slate-300',
};

export function projectStatusLabel(status) {
    const value = status?.value ?? status;

    return STATUS_LABELS[value] || statusLabel(value);
}

export function projectStatusDotClass(status) {
    const value = status?.value ?? status;

    return STATUS_DOT_CLASSES[value] || 'bg-slate-400';
}

export function formatModuleLabel(key) {
    return PROJECT_MODULE_OPTIONS.find((m) => m.key === key)?.label || key;
}

export function enabledModuleLabels(modules = {}) {
    return PROJECT_MODULE_OPTIONS.filter((m) => modules[m.key]).map((m) => m.label);
}
