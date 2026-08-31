import Card from '@/Components/Shared/Card';

function Metric({ label, value }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-4 text-center">
            <p className="text-lg font-semibold text-slate-900">{Number(value || 0).toFixed(1)}</p>
            <p className="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-500">{label}</p>
        </div>
    );
}

export default function WeeklySummaryPanel({ timesheet }) {
    return (
        <Card title="Weekly Summary">
            <div className="grid grid-cols-2 gap-3">
                <Metric label="Regular" value={timesheet.regular_hours} />
                <Metric label="Overtime" value={timesheet.overtime_hours} />
                <Metric label="Travel" value={timesheet.travel_hours} />
                <Metric label="Standby" value={timesheet.standby_hours} />
                <Metric label="Weekly Total" value={timesheet.hours} />
                <Metric label="Equipment" value={timesheet.equipment_hours} />
            </div>
        </Card>
    );
}
