import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import Card from '@/Components/Shared/Card';
import { cn } from '@/utils/helpers';

const COLOR_MAP = {
    ready: { fill: '#16A34A', swatch: 'bg-success' },
    'at risk': { fill: '#EA580C', swatch: 'bg-warning' },
    at_risk: { fill: '#EA580C', swatch: 'bg-warning' },
    'not ready': { fill: '#DC2626', swatch: 'bg-danger' },
    not_ready: { fill: '#DC2626', swatch: 'bg-danger' },
    'pending review': { fill: '#7C3AED', swatch: 'bg-journey' },
    pending_review: { fill: '#7C3AED', swatch: 'bg-journey' },
};

function resolveColor(item) {
    const key = String(item.name || '').toLowerCase();
    return COLOR_MAP[key] || { fill: item.color || '#94A3B8', swatch: 'bg-slate-400' };
}

export default function ReadinessDonutChart({ overview = [], total = 0, dataAsOf }) {
    const data = overview.map((item) => {
        const tone = resolveColor(item);
        return { ...item, fill: tone.fill, swatch: tone.swatch };
    });

    return (
        <Card padding={false} className="rounded-lg p-3">
            <h3 className="text-[10px] font-bold text-slate-800">Readiness Overview</h3>
            <div className="mt-2 grid min-h-[165px] grid-cols-[145px_1fr] items-center gap-2">
                <div className="relative h-[145px] w-[145px]">
                {data.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-xs text-slate-500">
                        No overview data
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                dataKey="value"
                                nameKey="name"
                                innerRadius={42}
                                outerRadius={61}
                                paddingAngle={1}
                            >
                                {data.map((entry) => (
                                    <Cell key={entry.name} fill={entry.fill} />
                                ))}
                            </Pie>
                            <Tooltip />
                        </PieChart>
                    </ResponsiveContainer>
                )}
                    {data.length > 0 && (
                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-xl font-bold text-slate-900">{total}</span>
                            <span className="text-[8px] text-slate-500">Total Workers</span>
                        </div>
                    )}
                </div>
                <ul className="space-y-2">
                    {data.map((item) => {
                        const pct = total > 0 ? Math.round((Number(item.value) / total) * 100) : 0;

                        return (
                            <li key={item.name} className="grid grid-cols-[8px_1fr_auto] items-center gap-1.5 text-[8px]">
                                <span className={cn('h-1.5 w-1.5 rounded-full', item.swatch)} />
                                <span className="text-slate-600">{item.name}</span>
                                <span className="font-semibold text-slate-900">
                                    {item.value} ({pct}%)
                                </span>
                            </li>
                        );
                    })}
                </ul>
            </div>
            {dataAsOf && (
                <p className="mt-1 flex items-center gap-1 text-[7px] text-slate-400">
                    <span className="h-1.5 w-1.5 rounded-full bg-success" />
                    Data as of {dataAsOf}
                </p>
            )}
        </Card>
    );
}
