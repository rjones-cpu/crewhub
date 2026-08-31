const FIELDS = [
    { key: 'regular_hours', label: 'Regular Hours' },
    { key: 'overtime_hours', label: 'OT1 (1.5x)' },
    { key: 'double_time_hours', label: 'OT2 (2x)' },
    { key: 'hours', label: 'Total Hours' },
];

export default function WeeklySummaryCard({ totals = {} }) {
    return (
        <div className="card card-padding h-full">
            <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                Weekly Summary
            </h2>

            <dl className="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {FIELDS.map((field) => (
                    <div key={field.key}>
                        <dt className="text-[10px] text-slate-500">{field.label}</dt>
                        <dd className="mt-1 text-base font-semibold text-slate-900">
                            {Number(totals[field.key] || 0).toFixed(2)}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
