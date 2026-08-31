import { PieChart as PieChartIcon } from 'lucide-react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import Card from '@/Components/Shared/Card';
import { formatNumber, formatPercent } from '@/utils/formatters';
import PanelTitle from './PanelTitle';

export default function ApprovalStatusDonut({ breakdown = {} }) {
    const { total = 0, segments = [], legend = [], note } = breakdown;

    return (
        <Card
            title={
                <PanelTitle icon={PieChartIcon} info>
                    Approval Status Breakdown
                </PanelTitle>
            }
            className="h-full"
        >
            <div className="relative mx-auto h-44 w-full max-w-[200px]">
                {segments.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-slate-500">
                        No approval data
                    </div>
                ) : (
                    <>
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={segments}
                                    dataKey="value"
                                    nameKey="name"
                                    innerRadius={52}
                                    outerRadius={80}
                                    paddingAngle={1}
                                    startAngle={90}
                                    endAngle={-270}
                                >
                                    {segments.map((segment) => (
                                        <Cell key={segment.name} fill={segment.color} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <p className="text-2xl font-semibold leading-none text-slate-900">
                                {formatNumber(total)}
                            </p>
                            <p className="mt-1 text-xs text-slate-500">Total</p>
                        </div>
                    </>
                )}
            </div>

            <ul className="mt-3 space-y-1.5">
                {legend.map((item) => (
                    <li key={item.name} className="flex items-start gap-2 text-xs">
                        <span
                            className="mt-1 h-2 w-2 shrink-0 rounded-full"
                            style={{ backgroundColor: item.color }}
                        />
                        <span className="min-w-0">
                            <span className="block text-slate-700">{item.name}</span>
                            <span className="block font-medium text-slate-900">
                                {formatNumber(item.value)} ({formatPercent(item.pct, 1)})
                            </span>
                        </span>
                    </li>
                ))}
            </ul>

            {note && <p className="mt-3 text-[11px] italic leading-snug text-slate-500">{note}</p>}
        </Card>
    );
}
