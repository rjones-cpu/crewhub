import { Info, Plus, Trash2 } from 'lucide-react';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

function blankEntry(index) {
    return {
        id: `eq-${Date.now()}-${index}`,
        date: null,
        equipment_type: '',
        unit_id: '',
        start_time: '',
        end_time: '',
        hours: 0,
        work_code: '',
        notes: '',
    };
}

function Cell({ editable, value, onChange, className = '', ...props }) {
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

export default function EquipmentUsagePanel({ entries = [], editable, onChange }) {
    const updateRow = (index, field, value) => {
        onChange(
            entries.map((row, position) =>
                position === index
                    ? { ...row, [field]: field === 'hours' ? Number(value || 0) : value }
                    : row,
            ),
        );
    };

    return (
        <div className="card">
            <div className="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <h2 className="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">
                    Equipment Usage
                    <Info className="h-3.5 w-3.5 text-slate-400" />
                </h2>
                {editable && (
                    <button
                        type="button"
                        onClick={() => onChange([...entries, blankEntry(entries.length)])}
                        className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-medium text-brand transition hover:bg-brand-soft"
                    >
                        <Plus className="h-3 w-3" />
                        Add Equipment Entry
                    </button>
                )}
            </div>

            {entries.length === 0 ? (
                <EmptyState
                    title="No equipment recorded"
                    description="Add an entry for each piece of plant used during the period."
                />
            ) : (
                <div className="table-wrap">
                    <table className="data-table timesheet-detail-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Equipment</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th>Work Code / Description</th>
                                <th>Notes</th>
                                {editable && <th className="w-8" />}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {entries.map((row, index) => (
                                <tr key={row.id || index}>
                                    <td className="text-slate-700">
                                        {formatDate(row.date, {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })}
                                    </td>
                                    <td>
                                        <Cell
                                            editable={editable}
                                            value={row.equipment_type}
                                            onChange={(value) => updateRow(index, 'equipment_type', value)}
                                            className="min-w-[8rem]"
                                        />
                                    </td>
                                    <td>
                                        <Cell
                                            editable={editable}
                                            value={row.start_time}
                                            onChange={(value) => updateRow(index, 'start_time', value)}
                                            className="w-20"
                                        />
                                    </td>
                                    <td>
                                        <Cell
                                            editable={editable}
                                            value={row.end_time}
                                            onChange={(value) => updateRow(index, 'end_time', value)}
                                            className="w-20"
                                        />
                                    </td>
                                    <td>
                                        {editable ? (
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.25"
                                                className="input-field h-8 w-16 py-0 text-xs"
                                                value={row.hours ?? 0}
                                                onChange={(event) =>
                                                    updateRow(index, 'hours', event.target.value)
                                                }
                                            />
                                        ) : (
                                            Number(row.hours || 0).toFixed(2)
                                        )}
                                    </td>
                                    <td>
                                        <Cell
                                            editable={editable}
                                            value={row.work_code || row.work_activity}
                                            onChange={(value) => updateRow(index, 'work_code', value)}
                                            className="min-w-[9rem]"
                                        />
                                    </td>
                                    <td>
                                        <Cell
                                            editable={editable}
                                            value={row.notes || row.fuel_notes}
                                            onChange={(value) => updateRow(index, 'notes', value)}
                                            className="min-w-[8rem]"
                                        />
                                    </td>
                                    {editable && (
                                        <td>
                                            <button
                                                type="button"
                                                aria-label="Remove equipment entry"
                                                onClick={() => onChange(
                                                    entries.filter((_, position) => position !== index),
                                                )}
                                                className="text-slate-300 transition hover:text-danger"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
