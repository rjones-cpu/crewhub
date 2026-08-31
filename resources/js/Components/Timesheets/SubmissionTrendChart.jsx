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

export default function SubmissionTrendChart({ data = [] }) {
    return (
        <Card title="Timesheet Submission Trend" className="h-full">
            <div className="h-64 w-full">
                {data.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-slate-500">
                        No trend data for this period
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                            <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#64748B' }} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#64748B' }} />
                            <Tooltip />
                            <Legend />
                            <Line
                                type="monotone"
                                dataKey="expected"
                                name="Expected"
                                stroke="#3B82F6"
                                strokeDasharray="6 4"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="submitted"
                                name="Submitted"
                                stroke="#16A34A"
                                strokeWidth={2}
                                dot={{ r: 3 }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </div>
        </Card>
    );
}
