import { cn } from '@/utils/helpers';

const METRICS = [
    { key: 'completed', label: 'Completed', tone: 'text-slate-900' },
    { key: 'in_progress', label: 'In Progress', tone: 'text-brand' },
    { key: 'expired', label: 'Expired', tone: 'text-danger' },
    { key: 'expiring_soon', label: 'Expiring Soon', tone: 'text-warning', note: '(within 30 days)' },
];

export default function TrainingComplianceOverview({ summary = {} }) {
    const percent = Number(summary.compliance_percent ?? 0);
    const requiredMet = summary.required_met ?? 0;
    const requiredTotal = summary.required_total ?? 0;

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <h3 className="text-[11px] font-semibold text-slate-900">Training Compliance Overview</h3>

            <div className="mt-3 flex flex-wrap items-start gap-x-8 gap-y-3">
                <div>
                    <p className="text-2xl font-bold text-success">{percent}%</p>
                    <p className="text-[9px] text-slate-500">Compliant</p>
                </div>

                {METRICS.map((metric) => (
                    <div key={metric.key}>
                        <p className={cn('text-2xl font-bold', metric.tone)}>{summary[metric.key] ?? 0}</p>
                        <p className="text-[9px] text-slate-500">{metric.label}</p>
                        {metric.note && <p className="text-[8px] text-slate-400">{metric.note}</p>}
                    </div>
                ))}
            </div>

            <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    className="h-full rounded-full bg-success transition-[width] duration-500"
                    style={{ width: `${Math.min(Math.max(percent, 0), 100)}%` }}
                />
            </div>

            <div className="mt-1.5 flex items-center justify-between text-[9px] text-slate-500">
                <span>
                    {requiredMet} of {requiredTotal} required trainings completed
                </span>
                <span>{summary.pending ?? 0} pending</span>
            </div>
        </section>
    );
}
