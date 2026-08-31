import { MoreVertical } from 'lucide-react';
import { formatDate } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

const HOUR_FIELDS = ['break_hours', 'regular_hours', 'overtime_hours', 'double_time_hours'];

const COLUMNS = [
    { key: 'break_hours', label: 'Break (hrs)' },
    { key: 'regular_hours', label: 'Regular Hours' },
    { key: 'overtime_hours', label: 'OT1 (1.5x)' },
    { key: 'double_time_hours', label: 'OT2 (2x)' },
];

function total(row) {
    return (
        Number(row.regular_hours || 0) +
        Number(row.overtime_hours || 0) +
        Number(row.double_time_hours || 0) +
        Number(row.travel_hours || 0) +
        Number(row.standby_hours || 0)
    );
}

function sum(entries, key) {
    return entries.reduce((carry, row) => carry + Number(row[key] || 0), 0);
}

function TextCell({ editable, value, onChange, className = '', ...props }) {
    if (!editable) {
        return <span className="text-slate-700">{value || '—'}</span>;
    }

    return (
        <input
            className={cn('input-field h-8 py-0 text-xs', className)}
            value={value ?? ''}
            onChange={(event) => onChange(event.target.value)}
            {...props}
        />
    );
}

export default function TimeEntryTable({ entries = [], editable, showHourlyRate, onChange }) {
    const updateRow = (index, field, value) => {
        onChange(
            entries.map((row, position) => {
                if (position !== index) {
                    return row;
                }

                const updated = {
                    ...row,
                    [field]: HOUR_FIELDS.includes(field) || field === 'hourly_rate'
                        ? Number(value || 0)
                        : value,
                };
                updated.total_hours = total(updated);

                return updated;
            }),
        );
    };

    return (
        <div className="card">
            <div className="border-b border-slate-100 px-4 py-3">
                <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                    Time Entry
                </h2>
            </div>

            <div className="table-wrap">
                <table className="data-table timesheet-detail-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            {COLUMNS.map((column) => (
                                <th key={column.key}>{column.label}</th>
                            ))}
                            <th>Total Hours</th>
                            <th>Work Code / Description</th>
                            {showHourlyRate && <th>Hourly Rate</th>}
                            <th className="w-8" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {entries.map((row, index) => {
                            const off = !row.start_time && !row.end_time;

                            return (
                                <tr key={row.date || index} className={cn(off && 'text-slate-400')}>
                                    <td className="text-slate-700">
                                        {formatDate(row.date, {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })}
                                    </td>
                                    <td className="text-slate-500">{row.day_label}</td>
                                    <td>
                                        <TextCell
                                            editable={editable}
                                            value={row.start_time}
                                            onChange={(value) => updateRow(index, 'start_time', value)}
                                            className="w-20"
                                        />
                                    </td>
                                    <td>
                                        <TextCell
                                            editable={editable}
                                            value={row.end_time}
                                            onChange={(value) => updateRow(index, 'end_time', value)}
                                            className="w-20"
                                        />
                                    </td>
                                    {COLUMNS.map((column) => (
                                        <td key={column.key}>
                                            {editable ? (
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.25"
                                                    className="input-field h-8 w-16 py-0 text-xs"
                                                    value={row[column.key] ?? 0}
                                                    onChange={(event) =>
                                                        updateRow(index, column.key, event.target.value)
                                                    }
                                                />
                                            ) : (
                                                Number(row[column.key] || 0).toFixed(2)
                                            )}
                                        </td>
                                    ))}
                                    <td className="font-semibold text-slate-900">
                                        {Number(row.total_hours || 0).toFixed(2)}
                                    </td>
                                    <td>
                                        <TextCell
                                            editable={editable}
                                            value={row.work_code || row.task}
                                            onChange={(value) => updateRow(index, 'work_code', value)}
                                            className="min-w-[9rem]"
                                        />
                                    </td>
                                    {showHourlyRate && (
                                        <td>
                                            {editable ? (
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    className="input-field h-8 w-20 py-0 text-xs"
                                                    value={row.hourly_rate ?? ''}
                                                    onChange={(event) =>
                                                        updateRow(index, 'hourly_rate', event.target.value)
                                                    }
                                                />
                                            ) : (
                                                <span className="text-slate-700">
                                                    {row.hourly_rate
                                                        ? `$${Number(row.hourly_rate).toFixed(2)}`
                                                        : '—'}
                                                </span>
                                            )}
                                        </td>
                                    )}
                                    <td>
                                        <button
                                            type="button"
                                            aria-label={`Row options for ${row.day_label}`}
                                            className="text-slate-300 transition hover:text-slate-500"
                                        >
                                            <MoreVertical className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                    <tfoot>
                        <tr className="bg-slate-50 font-semibold text-slate-900">
                            <td colSpan={4}>TOTALS</td>
                            {COLUMNS.map((column) => (
                                <td key={column.key}>{sum(entries, column.key).toFixed(2)}</td>
                            ))}
                            <td>{sum(entries, 'total_hours').toFixed(2)}</td>
                            <td colSpan={showHourlyRate ? 3 : 2} />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    );
}
