export const NAV_ITEMS = [
    { name: 'Company Command', route: 'dashboard', icon: 'LayoutDashboard' },
    { name: 'Workers', route: 'workers.index', icon: 'Users' },
    { name: 'Hierarchy', route: 'hierarchy.index', icon: 'Network' },
    { name: 'Major Projects', route: 'major-projects.index', icon: 'Briefcase' },
    { name: 'Schedule', route: 'schedule.index', icon: 'Calendar' },
    {
        name: 'Timesheets',
        route: 'timesheets.index',
        icon: 'Clock',
        children: [
            { name: 'Timesheets', route: 'timesheets.index' },
            { name: 'Timesheet', route: 'timesheets.entry' },
            { name: 'Timesheet Approval', route: 'timesheets.approval' },
            { name: 'Reports', route: 'timesheets.reports' },
        ],
    },
    { name: 'Readiness', route: 'readiness.index', icon: 'ShieldCheck' },
    { name: 'Journey Management', route: 'journeys.index', icon: 'Plane' },
    { name: 'Accommodation', route: 'accommodations.index', icon: 'BedDouble' },
    { name: 'LMS', route: 'lms.index', icon: 'GraduationCap' },
    { name: 'Communications', route: 'communications.index', icon: 'MessageSquare' },
    { name: 'Equipment', route: 'equipment.index', icon: 'Truck' },
    { name: 'Documents', route: 'documents.index', icon: 'FileText' },
    { name: 'Settings', route: 'settings.index', icon: 'Settings' },
];

// Sub-navigation for the Major Projects hub. Role-gated tabs are filtered in the page.
export const MAJOR_PROJECT_TABS = [
    { name: 'Current Projects', route: 'major-projects.index', icon: 'CalendarCheck' },
    { name: 'Join a Project', route: 'major-projects.join', icon: 'UserRoundPlus' },
    { name: 'Create a Project', route: 'major-projects.create', icon: 'SquarePlus' },
];

// Shortcuts pinned below the main navigation in the sidebar.
export const QUICK_ACTIONS = [
    { name: 'Add Worker', route: 'workers.create', icon: 'UserPlus' },
    { name: 'Create Journey', route: 'journeys.index', icon: 'PlaneTakeoff' },
    { name: 'Report Issue', route: 'readiness.index', icon: 'AlertCircle' },
    { name: 'New Message', route: 'communications.index', icon: 'Mail' },
];

export const JOURNEY_NAV = [
    { name: 'Registered Vehicles', route: 'journeys.vehicles', icon: 'Truck' },
    { name: 'Journey Questions', route: 'journeys.questions', icon: 'CircleHelp' },
    { name: 'All Journey List', route: 'journeys.index', icon: 'List' },
    { name: 'Calculate Risk', route: 'journeys.risk', icon: 'TriangleAlert' },
    { name: 'Designation to Journey Hub', route: 'journeys.hubs', icon: 'MapPin' },
    { name: 'Confirmation of Vehicle Insurance', route: 'journeys.insurance', icon: 'ShieldCheck' },
];

export const JOURNEY_STATUS_OPTIONS = [
    { value: 'pending', label: 'Pending Approval' },
    { value: 'approved', label: 'Planned' },
    { value: 'in_transit', label: 'En Route' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

export const JOURNEY_RISK_OPTIONS = [
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

// Sub-navigation shown as tabs in the page header for the Timesheets area.
export const TIMESHEET_TABS = [
    { name: 'Timesheets', route: 'timesheets.index' },
    { name: 'Timesheet', route: 'timesheets.entry' },
    { name: 'Timesheet Approval', route: 'timesheets.approval' },
    { name: 'Reports', route: 'timesheets.reports' },
];

export const STATUS_COLORS = {
    ready: 'success',
    active: 'success',
    approved: 'success',
    fully_approved: 'success',
    manager_approved: 'success',
    completed: 'success',
    checked_in: 'success',
    accepted: 'success',
    valid: 'success',
    reserved: 'brand',
    invited: 'brand',
    in_progress: 'brand',
    in_transit: 'brand',
    checked_out: 'slate',
    cancelled: 'slate',
    not_started: 'slate',
    draft: 'warning',
    at_risk: 'warning',
    pending: 'warning',
    planned: 'warning',
    submitted: 'warning',
    returned: 'warning',
    action_required: 'warning',
    warning: 'warning',
    medium: 'warning',
    low: 'slate',
    not_ready: 'danger',
    critical: 'danger',
    high: 'danger',
    rejected: 'danger',
    declined: 'danger',
    overdue: 'danger',
    missing: 'danger',
    expired: 'danger',
    journey: 'journey',
    pending_review: 'journey',
};
