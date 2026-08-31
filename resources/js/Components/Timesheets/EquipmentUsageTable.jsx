import { Plus, Trash2 } from 'lucide-react';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

function emptyEquipmentLine(supervisor) {
    return {
        id: `eq-${Date.now()}`,
        day: '',
        date: '',
        equipment_type: '',
        unit_id: '',
        start_time: '',
        end_time: '',
        hours: 0,
        cost_code: '',
        work_activity: '',
        fuel_notes: '',
        manager: supervisor || '',
    };
}

export default function EquipmentUsageTable({ entries = [], editable, onChange, supervisor }) {
    const total = entries.reduce((sum, row) => sum + Number(row.hours || 0), 0);

    const updateRow = (index, field, value) => {
        const next = entries.map((row, i) =>
            i === index
                ? {
                      ...row,
                      [field]: field === 'hours' ? Number(value || 0) : value,
                  }
                : row,
        );
        onChange?.(next);
    };

    const addLine = () => onChange?.([...entries, emptyEquipmentLine(supervisor)]);
    const removeLine = (index) => onChange?.(entries.filter((_, i) => i !== index));

    return (
        <Card
            title="Equipment Usage Tracker"
            actions={
                editable ? (
                    <Button variant="secondary" onClick={addLine}>
                        <Plus className="h-4 w-4" />
                        Add Equipment Line
                    </Button>
                ) : null
            }
        >
            <div className="-mx-4 table-wrap sm:-mx-5">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Equipment Type</th>
                            <th>Unit / Asset ID</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Hours</th>
                            <th>Cost Code</th>
                            <th>Work Activity</th>
                            <th>Fuel / Meter</th>
                            <th>Manager</th>
                            {editable && <th />}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {entries.length === 0 ? (
                            <tr>
                                <td colSpan={editable ? 11 : 10} className="text-slate-500">
                                    No equipment lines yet.
                                </td>
                            </tr>
                        ) : (
                            entries.map((row, index) => (
                                <tr key={row.id || index}>
                                    {[
                                        ['day', 'w-16'],
                                        ['equipment_type', 'min-w-[7rem]'],
                                        ['unit_id', 'w-24'],
                                        ['start_time', 'w-20'],
                                        ['end_time', 'w-20'],
                                        ['hours', 'w-16', 'number'],
                                        ['cost_code', 'w-24'],
                                        ['work_activity', 'min-w-[7rem]'],
                                        ['fuel_notes', 'min-w-[7rem]'],
                                        ['manager', 'min-w-[7rem]'],
                                    ].map(([field, width, type]) => (
                                        <td key={field}>
                                            {editable ? (
                                                <input
                                                    type={type || 'text'}
                                                    min={type === 'number' ? '0' : undefined}
                                                    step={type === 'number' ? '0.5' : undefined}
                                                    className={`input-field ${width}`}
                                                    value={row[field] ?? ''}
                                                    onChange={(e) => updateRow(index, field, e.target.value)}
                                                />
                                            ) : field === 'hours' ? (
                                                Number(row.hours || 0).toFixed(1)
                                            ) : (
                                                row[field] || '—'
                                            )}
                                        </td>
                                    ))}
                                    {editable && (
                                        <td>
                                            <button
                                                type="button"
                                                onClick={() => removeLine(index)}
                                                className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-danger"
                                                aria-label="Remove line"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                    <tfoot>
                        <tr className="bg-slate-50 font-semibold text-slate-900">
                            <td colSpan={5}>Total Equipment Hours</td>
                            <td>{total.toFixed(1)}</td>
                            <td colSpan={editable ? 5 : 4} />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>
    );
}
