import { BarChart3 } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    LabelList,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import Card from '@/Components/Shared/Card';
import { formatNumber } from '@/utils/formatters';
import PanelTitle from './PanelTitle';

const formatHours = (value) => formatNumber(value, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const formatAxisTick = (value) => (value >= 1000 ? `${value / 1000}K` : String(value));

export default function HoursByPositionChart({ data = [] }) {
    return (
        <Card
            title={
                <PanelTitle icon={BarChart3} info>
                    Hours by Position / Trade
                </PanelTitle>
            }
            className="h-full"
        >
            <div className="h-64 w-full">
                {data.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-slate-500">
                        No hours recorded for this period
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={data}
                            layout="vertical"
                            margin={{ top: 4, right: 56, left: 8, bottom: 16 }}
                            barCategoryGap="22%"
                        >
                            <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" horizontal={false} />
                            <XAxis
                                type="number"
                                domain={[0, 2000]}
                                ticks={[0, 500, 1000, 1500, 2000]}
                                tickFormatter={formatAxisTick}
                                tick={{ fontSize: 10, fill: '#64748B' }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                                label={{
                                    value: 'Hours',
                                    position: 'insideBottom',
                                    offset: -10,
                                    fontSize: 10,
                                    fill: '#64748B',
                                }}
                            />
                            <YAxis
                                type="category"
                                dataKey="position"
                                width={104}
                                tick={{ fontSize: 10, fill: '#475569' }}
                                tickLine={false}
                                axisLine={false}
                            />
                            <Tooltip formatter={(value) => formatHours(value)} />
                            <Bar dataKey="hours" fill="#2563EB" radius={[0, 3, 3, 0]}>
                                <LabelList
                                    dataKey="hours"
                                    position="right"
                                    formatter={formatHours}
                                    style={{ fontSize: 10, fill: '#334155' }}
                                />
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </div>
        </Card>
    );
}
