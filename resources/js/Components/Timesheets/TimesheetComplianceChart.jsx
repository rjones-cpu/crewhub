import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import Card from '@/Components/Shared/Card';
import { formatPercent } from '@/utils/formatters';

export default function TimesheetComplianceChart({ data = [], compliancePct = 0, target = 95 }) {
    const total = data.reduce((sum, row) => sum + Number(row.value || 0), 0);

    return (
        <div className="space-y-4">
            <Card title="Timesheet Compliance" className="h-full">
                <div className="relative mx-auto h-52 w-full max-w-[220px]">
                    {total === 0 ? (
                        <div className="flex h-full items-center justify-center text-sm text-slate-500">
                            No compliance data
                        </div>
                    ) : (
                        <>
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={data}
                                        dataKey="value"
                                        nameKey="name"
                                        innerRadius={55}
                                        outerRadius={80}
                                        paddingAngle={2}
                                    >
                                        {data.map((entry) => (
                                            <Cell key={entry.name} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <p className="text-2xl font-semibold text-slate-900">
                                    {formatPercent(compliancePct)}
                                </p>
                                <p className="text-xs text-slate-500">Compliance</p>
                            </div>
                        </>
                    )}
                </div>
                <ul className="mt-2 flex flex-wrap justify-center gap-3">
                    {data.map((item) => (
                        <li key={item.name} className="flex items-center gap-1.5 text-xs text-slate-600">
                            <span
                                className="h-2.5 w-2.5 rounded-full"
                                style={{ backgroundColor: item.color }}
                            />
                            {item.name}
                        </li>
                    ))}
                </ul>
            </Card>

            <Card title="Compliance Target">
                <p className="kpi-value">{formatPercent(target)}</p>
                <p className="mt-2 text-sm text-slate-500">
                    Target on-time submission and approval rate for the selected period.
                </p>
            </Card>
        </div>
    );
}
