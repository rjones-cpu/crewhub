import { TrendingUp } from 'lucide-react';
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import Card from '@/Components/Shared/Card';
import PanelTitle from './PanelTitle';

const SERIES = [
    { key: 'submitted', name: 'Submitted', color: '#2563EB' },
    { key: 'manager_approved', name: 'Manager Approved', color: '#16A34A' },
    { key: 'client_approved', name: 'Client Approved', color: '#7C3AED' },
];

export default function SubmissionApprovalTrendChart({ data = [] }) {
    return (
        <Card
            title={
                <PanelTitle icon={TrendingUp} info>
                    Timesheet Submission &amp; Approval Trend
                </PanelTitle>
            }
            className="h-full"
        >
            <div className="h-64 w-full">
                {data.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-slate-500">
                        No trend data for this period
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data} margin={{ top: 16, right: 12, left: -12, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" vertical={false} />
                            <XAxis
                                dataKey="label"
                                tick={{ fontSize: 9, fill: '#64748B' }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                                interval={0}
                            />
                            <YAxis
                                allowDecimals={false}
                                tick={{ fontSize: 10, fill: '#64748B' }}
                                tickLine={false}
                                axisLine={false}
                            />
                            <Tooltip />
                            <Legend
                                iconType="circle"
                                iconSize={8}
                                wrapperStyle={{ fontSize: 11, paddingTop: 4 }}
                            />
                            {SERIES.map((series) => (
                                <Line
                                    key={series.key}
                                    type="monotone"
                                    dataKey={series.key}
                                    name={series.name}
                                    stroke={series.color}
                                    strokeWidth={2}
                                    dot={{ r: 3, strokeWidth: 0, fill: series.color }}
                                    label={{
                                        position: 'top',
                                        fontSize: 10,
                                        fill: series.color,
                                        offset: 6,
                                    }}
                                />
                            ))}
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </div>
        </Card>
    );
}
