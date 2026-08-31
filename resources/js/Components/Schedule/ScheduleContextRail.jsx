import { ArrowDown, ArrowUp, ChevronRight, Info, X } from 'lucide-react';
import { cn } from '@/utils/helpers';

const ALERT_DOT = {
    danger: 'bg-danger',
    info: 'bg-brand',
    warning: 'bg-amber-500',
};

function SectionTitle({ children }) {
    return <p className="text-[11px] font-semibold text-slate-900">{children}</p>;
}

function PositionsTable({ positions }) {
    const rows = positions?.rows || [];
    const total = positions?.total || {};

    if (rows.length === 0) {
        return <p className="mt-2 text-[10px] text-slate-400">No staffing matrix rows for this period.</p>;
    }

    return (
        <table className="mt-2 w-full table-fixed border-collapse">
            <thead>
                <tr className="text-[10px] font-medium text-slate-400">
                    <th className="w-2/5 pb-1 text-left font-medium">Position</th>
                    <th className="pb-1 text-right font-medium">Required</th>
                    <th className="pb-1 text-right font-medium">Scheduled</th>
                    <th className="pb-1 text-right font-medium">Shortage</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr key={row.position} className="text-[10px] text-slate-600">
                        <td className="truncate py-1 pr-1" title={row.position}>
                            {row.position}
                        </td>
                        <td className="py-1 text-right">{row.required ?? 0}</td>
                        <td className="py-1 text-right">{row.scheduled ?? 0}</td>
                        <td
                            className={cn(
                                'py-1 text-right font-semibold',
                                (row.shortage || 0) > 0 ? 'text-danger' : 'text-slate-400',
                            )}
                        >
                            {row.shortage ?? 0}
                        </td>
                    </tr>
                ))}
                <tr className="text-[10px] font-semibold text-slate-800">
                    <td className="border-t border-slate-200 pt-1.5">Total</td>
                    <td className="border-t border-slate-200 pt-1.5 text-right">{total.required ?? 0}</td>
                    <td className="border-t border-slate-200 pt-1.5 text-right">{total.scheduled ?? 0}</td>
                    <td
                        className={cn(
                            'border-t border-slate-200 pt-1.5 text-right',
                            (total.shortage || 0) > 0 ? 'text-danger' : 'text-slate-400',
                        )}
                    >
                        {total.shortage ?? 0}
                    </td>
                </tr>
            </tbody>
        </table>
    );
}

export default function ScheduleContextRail({
    rail = {},
    onClose = () => {},
    onViewSpecialRequests = () => {},
    onViewAlerts = () => {},
}) {
    const turnover = rail?.turnover || {};
    const alerts = rail?.alerts || [];

    return (
        <div className="card divide-y divide-slate-100">
            <div className="flex items-start justify-between gap-2 px-4 py-3">
                <h2 className="text-xs font-semibold text-slate-900">{rail?.title || 'Schedule Context'}</h2>
                <button
                    type="button"
                    onClick={onClose}
                    title="Close panel"
                    className="rounded-md p-0.5 text-slate-400 transition hover:bg-slate-50 hover:text-slate-600"
                >
                    <X className="h-3.5 w-3.5" />
                </button>
            </div>

            <div className="px-4 py-3">
                <SectionTitle>Required Positions (Staffing Matrix)</SectionTitle>
                <PositionsTable positions={rail?.positions} />
            </div>

            <div className="px-4 py-3">
                <SectionTitle>Arrivals / Departures</SectionTitle>
                <div className="mt-2 flex items-center gap-6 text-[10px] text-slate-600">
                    <span className="flex items-center gap-1.5">
                        <ArrowUp className="h-3 w-3 text-success" />
                        Arrivals
                        <span className="font-semibold text-slate-800">{rail?.arrivals ?? 0}</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <ArrowDown className="h-3 w-3 text-violet-500" />
                        Departures
                        <span className="font-semibold text-slate-800">{rail?.departures ?? 0}</span>
                    </span>
                </div>
            </div>

            <div className="px-4 py-3">
                <SectionTitle>Housekeeping Turnover Impact</SectionTitle>
                <div className="mt-2 flex items-center justify-between gap-2">
                    <span className="text-[10px] text-slate-600">{turnover.level || '—'}</span>
                    {turnover.is_high ? (
                        <span className="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700">
                            {turnover.impact}
                            <Info className="h-3 w-3 shrink-0" />
                        </span>
                    ) : (
                        <span className="text-[10px] text-slate-400">{turnover.impact || '—'}</span>
                    )}
                </div>
            </div>

            <div className="px-4 py-3">
                <button
                    type="button"
                    onClick={onViewSpecialRequests}
                    className="flex w-full items-center justify-between gap-2 text-left"
                >
                    <SectionTitle>Special Requests</SectionTitle>
                    <span className="flex items-center gap-1 text-[10px] font-semibold text-slate-600">
                        {rail?.special_requests ?? 0}
                        <ChevronRight className="h-3 w-3 text-slate-400" />
                    </span>
                </button>
            </div>

            <div className="px-4 py-3">
                <SectionTitle>Notes</SectionTitle>
                <p className="mt-2 text-[10px] leading-relaxed text-slate-500">{rail?.notes || 'No notes recorded.'}</p>
            </div>

            <div className="px-4 py-3">
                <SectionTitle>Schedule Alerts</SectionTitle>
                {alerts.length === 0 ? (
                    <p className="mt-2 text-[10px] text-slate-400">No alerts for this period.</p>
                ) : (
                    <ul className="mt-2 space-y-1.5">
                        {alerts.map((alert, index) => (
                            <li key={`${alert.tone}-${index}`} className="flex items-start gap-2 text-[10px] text-slate-600">
                                <span
                                    className={cn(
                                        'mt-1 h-1.5 w-1.5 shrink-0 rounded-full',
                                        ALERT_DOT[alert.tone] || ALERT_DOT.info,
                                    )}
                                />
                                <span className="leading-tight">{alert.label}</span>
                            </li>
                        ))}
                    </ul>
                )}
                <button
                    type="button"
                    onClick={onViewAlerts}
                    className="mt-3 text-[10px] font-medium text-brand hover:underline"
                >
                    View all alerts
                </button>
            </div>
        </div>
    );
}
