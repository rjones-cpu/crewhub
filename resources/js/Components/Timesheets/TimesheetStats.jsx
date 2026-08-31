import {
    AlertTriangle,
    CheckCircle2,
    ClipboardList,
    Clock,
    FileWarning,
    Percent,
    Users,
} from 'lucide-react';
import KpiCard from '@/Components/Dashboard/KpiCard';
import { formatNumber, formatPercent } from '@/utils/formatters';

export default function TimesheetStats({ stats = {} }) {
    const clientRequired = Boolean(stats.client_approval_required);

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            <KpiCard
                label="Client Approval"
                value={stats.client_approval_label || (clientRequired ? 'Required' : 'Not Required')}
                hint={clientRequired ? 'Enabled for this project' : 'Disabled for this project'}
                icon={ClipboardList}
                tone={clientRequired ? 'brand' : 'slate'}
            />
            <KpiCard
                label="Expected Timesheets"
                value={formatNumber(stats.expected)}
                hint="Across selected scope"
                icon={Users}
            />
            <KpiCard
                label="Submitted"
                value={formatNumber(stats.submitted)}
                hint={`${formatPercent(stats.submitted_pct)} of expected`}
                icon={CheckCircle2}
                tone="success"
            />
            <KpiCard
                label="Pending Approval"
                value={formatNumber(stats.pending_approval)}
                hint={`${formatPercent(stats.pending_pct)} of expected`}
                icon={Clock}
                tone="warning"
            />
            <KpiCard
                label="Missing Timesheets"
                value={formatNumber(stats.missing)}
                hint={`${formatPercent(stats.missing_pct)} of expected`}
                icon={FileWarning}
                tone="danger"
            />
            <KpiCard
                label="Approval Rate"
                value={formatPercent(stats.approval_rate)}
                hint="This period"
                icon={Percent}
                tone="success"
            />
            <KpiCard
                label="Approval Bottlenecks"
                value={formatNumber(stats.bottlenecks)}
                hint="Active approvers"
                icon={AlertTriangle}
                tone="warning"
            />
        </div>
    );
}
