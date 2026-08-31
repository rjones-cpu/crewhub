import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

const HOUR_FIELDS = [
    { key: 'break_hours', label: 'Break' },
    { key: 'regular_hours', label: 'Regular' },
    { key: 'overtime_hours', label: 'OT' },
    { key: 'double_time_hours', label: 'DT' },
    { key: 'travel_hours', label: 'Travel' },
    { key: 'standby_hours', label: 'Standby' },
];

function sumField(entries, key) {
    return entries.reduce((sum, row) => sum + Number(row[key] || 0), 0);
}

function recalculateTotal(row) {
    return (
        Number(row.regular_hours || 0) +
        Number(row.overtime_hours || 0) +
        Number(row.double_time_hours || 0) +
        Number(row.travel_hours || 0) +
        Number(row.standby_hours || 0)
    );
}

export default function DailyTimeEntryTable({ entries = [], editable, onChange, onCopyPrevious }) {
    const updateRow = (index, field, value) => {
        const next = entries.map((row, i) => {
            if (i !== index) {
                return row;
            }

            const updated = {
                ...row,
                [field]: ['break_hours', 'regular_hours', 'overtime_hours', 'double_time_hours', 'travel_hours', 'standby_hours'].includes(field)
                    ? Number(value || 0)
                    : value,
            };
            updated.total_hours = recalculateTotal(updated);

            return updated;
        });
        onChange?.(next);
    };

    return (
        <Card
            title="Daily Time Entry"
            actions={
                editable ? (
                    <Button variant="secondary" onClick={onCopyPrevious}>
                        Copy Previous Week
                    </Button>
                ) : null
            }
        >
            <div className="-mx-4 table-wrap sm:-mx-5">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Start</th>
                            <th>End</th>
                            {HOUR_FIELDS.map((field) => (
                                <th key={field.key}>{field.label}</th>
                            ))}
                            <th>Total</th>
                            <th>Work Location</th>
                            <th>Task / Activity</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {entries.map((row, index) => (
                            <tr key={row.date || index}>
                                <td>
                                    <div className="font-medium text-slate-900">{row.day_label}</div>
                                    <div className="text-xs text-slate-400">{row.date}</div>
                                </td>
                                <td>
                                    {editable ? (
                                        <select
                                            className="input-field min-w-[5rem]"
                                            value={row.shift || 'Day'}
                                            onChange={(e) => updateRow(index, 'shift', e.target.value)}
                                        >
                                            <option>Day</option>
                                            <option>Night</option>
                                            <option>Off</option>
                                        </select>
                                    ) : (
                                        row.shift || '—'
                                    )}
                                </td>
                                <td>
                                    {editable ? (
                                        <input
                                            className="input-field w-20"
                                            value={row.start_time || ''}
                                            onChange={(e) => updateRow(index, 'start_time', e.target.value)}
                                        />
                                    ) : (
                                        row.start_time || '—'
                                    )}
                                </td>
                                <td>
                                    {editable ? (
                                        <input
                                            className="input-field w-20"
                                            value={row.end_time || ''}
                                            onChange={(e) => updateRow(index, 'end_time', e.target.value)}
                                        />
                                    ) : (
                                        row.end_time || '—'
                                    )}
                                </td>
                                {HOUR_FIELDS.map((field) => (
                                    <td key={field.key}>
                                        {editable ? (
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                className="input-field w-16"
                                                value={row[field.key] ?? 0}
                                                onChange={(e) => updateRow(index, field.key, e.target.value)}
                                            />
                                        ) : (
                                            Number(row[field.key] || 0).toFixed(1)
                                        )}
                                    </td>
                                ))}
                                <td className="font-medium text-slate-900">
                                    {Number(row.total_hours || 0).toFixed(1)}
                                </td>
                                <td>
                                    {editable ? (
                                        <input
                                            className="input-field min-w-[7rem]"
                                            value={row.work_location || ''}
                                            onChange={(e) => updateRow(index, 'work_location', e.target.value)}
                                        />
                                    ) : (
                                        row.work_location || '—'
                                    )}
                                </td>
                                <td>
                                    {editable ? (
                                        <input
                                            className="input-field min-w-[7rem]"
                                            value={row.task || ''}
                                            onChange={(e) => updateRow(index, 'task', e.target.value)}
                                        />
                                    ) : (
                                        row.task || '—'
                                    )}
                                </td>
                                <td>
                                    {editable ? (
                                        <input
                                            className="input-field min-w-[7rem]"
                                            value={row.notes || ''}
                                            onChange={(e) => updateRow(index, 'notes', e.target.value)}
                                        />
                                    ) : (
                                        row.notes || '—'
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr className="bg-slate-50 font-semibold text-slate-900">
                            <td colSpan={4}>Totals</td>
                            {HOUR_FIELDS.map((field) => (
                                <td key={field.key}>{sumField(entries, field.key).toFixed(1)}</td>
                            ))}
                            <td>{sumField(entries, 'total_hours').toFixed(1)}</td>
                            <td colSpan={3} />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>
    );
}
