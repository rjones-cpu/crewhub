import {
    CartesianGrid,
    ComposedChart,
    Legend,
    Line,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import Card from '@/Components/Shared/Card';
import { formatDate } from '@/utils/formatters';
import CardFooterLink from './CardFooterLink';

function chartLabel(item) {
    const raw = item.forecast_date || item.date;

    return formatDate(raw, { year: undefined, month: 'short', day: 'numeric' });
}

export default function WorkforceForecastChart({ forecast = [], href }) {
    const data = forecast.map((item) => ({
        ...item,
        label: chartLabel(item),
    }));

    return (
        <Card title="Workforce Outlook (14 Days)" className="flex h-full flex-col">
            <div className="min-h-[180px] w-full flex-1">
                {data.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-slate-500">
                        No forecast data available
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <ComposedChart data={data} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                            <XAxis
                                dataKey="label"
                                tick={{ fill: '#64748B', fontSize: 11 }}
                                minTickGap={8}
                                tickMargin={8}
                            />
                            <YAxis tick={{ fill: '#64748B', fontSize: 12 }} />
                            <Tooltip
                                contentStyle={{
                                    borderRadius: 12,
                                    borderColor: '#E2E8F0',
                                    fontSize: 12,
                                }}
                            />
                            <Legend
                                verticalAlign="top"
                                height={32}
                                iconType="plainline"
                                wrapperStyle={{ fontSize: 12, color: '#64748B' }}
                            />
                            <Line
                                type="monotone"
                                dataKey="required"
                                name="Required (Demand)"
                                stroke="#2563EB"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="scheduled"
                                name="Scheduled"
                                stroke="#16A34A"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="gap"
                                name="Gap"
                                stroke="#DC2626"
                                strokeWidth={2}
                                strokeDasharray="4 4"
                                dot={false}
                            />
                        </ComposedChart>
                    </ResponsiveContainer>
                )}
            </div>

            {href && <CardFooterLink href={href}>View full history</CardFooterLink>}
        </Card>
    );
}
