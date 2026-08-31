import { Box, FileClock, GraduationCap, Info, Plane, ShieldCheck, Star } from 'lucide-react';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import { cn } from '@/utils/helpers';

const AREA_ICONS = {
    'Time Sheets': { icon: FileClock, className: 'text-brand' },
    'Equipment & Materials': { icon: Box, className: 'text-warning' },
    'Safety & Compliance': { icon: Star, className: 'text-amber-500' },
    Training: { icon: GraduationCap, className: 'text-brand' },
    'Journey Management': { icon: Plane, className: 'text-journey' },
};

const STATUS_TONES = {
    accepted: 'success',
    pending: 'warning',
    not_delegated: 'slate',
};

function Toggle({ checked, disabled, onChange, label }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition disabled:opacity-50',
                checked ? 'bg-brand' : 'bg-slate-300',
            )}
        >
            <span
                className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition',
                    checked ? 'translate-x-4' : 'translate-x-0.5',
                )}
            />
        </button>
    );
}

export default function DelegationTable({ delegations = [], canManage = true, onToggle }) {
    return (
        <Card
            title={
                <span className="flex flex-col">
                    Responsibility Delegation
                    <span className="text-xs font-normal text-slate-500">
                        Delegate responsibility areas to a connected manager for this project
                    </span>
                </span>
            }
        >
            <div className="-mx-4 table-wrap sm:-mx-5">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Responsibility Area</th>
                            <th>Delegated By</th>
                            <th>Status</th>
                            <th className="text-right">Delegable</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {delegations.map((row) => {
                            const area = AREA_ICONS[row.area] || {
                                icon: ShieldCheck,
                                className: 'text-slate-400',
                            };
                            const Icon = area.icon;

                            return (
                                <tr key={row.area}>
                                    <td>
                                        <span className="flex items-center gap-2 font-medium text-slate-900">
                                            <Icon
                                                className={cn('h-4 w-4 shrink-0', area.className)}
                                            />
                                            {row.area}
                                        </span>
                                    </td>
                                    <td>
                                        {row.manager_name ? (
                                            <span>
                                                {row.manager_name}
                                                {row.manager_relationship && (
                                                    <span className="text-slate-400">
                                                        {' '}
                                                        ({row.manager_relationship})
                                                    </span>
                                                )}
                                            </span>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td>
                                        <Badge tone={STATUS_TONES[row.status] || 'slate'}>
                                            {row.status_label}
                                        </Badge>
                                    </td>
                                    <td className="text-right">
                                        <Toggle
                                            checked={row.is_delegable}
                                            disabled={!canManage}
                                            label={`Delegate ${row.area}`}
                                            onChange={(value) => onToggle(row.area, value)}
                                        />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <p className="mt-4 flex items-start gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                Delegations are specific to this major project and its connected manager(s).
            </p>
        </Card>
    );
}
