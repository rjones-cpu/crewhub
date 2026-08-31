import { Cell, Pie, PieChart, ResponsiveContainer } from 'recharts';
import { cn } from '@/utils/helpers';
import CardFooterLink from './CardFooterLink';
import { gradeStyle } from './GradeBadge';

export default function ScorecardSummaryCard({
    summary = {},
    href,
    unit = 'Company',
    unitPlural = 'Companies',
}) {
    const breakdown = summary.breakdown || [];
    const total = summary.total ?? 0;
    const slices = breakdown.filter((item) => item.count > 0);

    return (
        <div className="card flex h-full flex-col p-4">
            <h3 className="section-title">Scorecard Summary</h3>

            <div className="mt-2 flex flex-1 items-center gap-3">
                <div className="relative h-[120px] w-[45%] shrink-0">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={slices.length > 0 ? slices : [{ grade: 'empty', count: 1 }]}
                                dataKey="count"
                                innerRadius="62%"
                                outerRadius="92%"
                                paddingAngle={1}
                                stroke="none"
                                isAnimationActive={false}
                            >
                                {(slices.length > 0 ? slices : [{ grade: 'empty' }]).map((item) => (
                                    <Cell
                                        key={item.grade}
                                        fill={item.grade === 'empty' ? '#E2E8F0' : gradeStyle(item.grade).hex}
                                    />
                                ))}
                            </Pie>
                        </PieChart>
                    </ResponsiveContainer>

                    <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span className="text-xl font-bold leading-none text-slate-900">{total}</span>
                        <span className="mt-0.5 text-center text-[8px] leading-tight text-slate-500">
                            Total
                            <br />
                            {unitPlural}
                        </span>
                    </div>
                </div>

                <ul className="min-w-0 flex-1 space-y-1.5">
                    {breakdown.map((item) => (
                        <li key={item.grade} className="flex items-start gap-1.5">
                            <span
                                className={cn(
                                    'mt-0.5 h-2 w-2 shrink-0 rounded-sm',
                                    gradeStyle(item.grade).dot,
                                )}
                            />
                            <span className="min-w-0">
                                <span className="block text-[10px] font-semibold leading-tight text-slate-800">
                                    {item.grade} ({item.status})
                                </span>
                                <span className="block text-[9px] leading-tight text-slate-500">
                                    {item.count} {item.count === 1 ? unit : unitPlural} ({item.percent}%)
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <CardFooterLink href={href}>View all {unitPlural.toLowerCase()}</CardFooterLink>
        </div>
    );
}
