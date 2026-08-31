import { Car, CircleDollarSign, HardHat, Package, Sun } from 'lucide-react';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';

const REQUIREMENTS = [
    { key: 'mileage', label: 'Mileage Tracking', icon: Car },
    { key: 'equipment', label: 'Equipment Tracking', icon: HardHat },
    { key: 'materials', label: 'Materials Tracking', icon: Package },
    { key: 'hourly_rate', label: 'Hourly Rate', icon: CircleDollarSign },
    { key: 'day_rate', label: 'Day Rate', icon: Sun },
];

export default function TimesheetRequirements({ requirements = {}, editable, onChange }) {
    return (
        <div className="card card-padding">
            <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                Timesheet Requirements
            </h2>
            <p className="mt-1 text-[11px] text-slate-500">
                Enable the requirements you need to track on this timesheet.
            </p>

            <div className="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                {REQUIREMENTS.map(({ key, label, icon: Icon }) => (
                    <div
                        key={key}
                        className="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2"
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            <Icon className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="truncate text-[11px] font-medium text-slate-700">
                                {label}
                            </span>
                        </span>
                        <ToggleSwitch
                            size="sm"
                            label={label}
                            checked={Boolean(requirements[key])}
                            disabled={!editable}
                            onChange={(value) => onChange({ ...requirements, [key]: value })}
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}
