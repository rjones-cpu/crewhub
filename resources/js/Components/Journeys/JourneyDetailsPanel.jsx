import { Link } from '@inertiajs/react';
import {
    CalendarCheck2,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    CircleDot,
    Circle,
    MapPin,
    Phone,
    Route,
    TriangleAlert,
    Truck,
    User,
    X,
} from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import { JOURNEY_STATUS_OPTIONS } from '@/utils/constants';
import { formatDateTime } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import { JourneyStatusBadge, RiskMeter, journeyStatusValue } from './journeyHelpers';

function DetailRow({ icon: Icon, label, value }) {
    return (
        <div className="flex gap-2.5 py-1.5">
            <Icon className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" strokeWidth={1.8} />
            <div className="min-w-0">
                <p className="text-[11px] text-slate-500">{label}</p>
                <p className="mt-0.5 text-xs leading-relaxed text-slate-800">{value || '—'}</p>
            </div>
        </div>
    );
}

function CheckpointTimeline({ checkpoints = [] }) {
    if (!checkpoints.length) {
        return <p className="text-xs text-slate-500">No checkpoints recorded.</p>;
    }

    return (
        <ol className="space-y-3">
            {checkpoints.map((point, index) => {
                const status = point.status || 'pending';
                const Icon = status === 'completed'
                    ? CheckCircle2
                    : status === 'in_progress'
                        ? CircleDot
                        : Circle;

                return (
                    <li key={`${point.name}-${index}`} className="flex gap-2.5">
                        <Icon
                            className={cn(
                                'mt-0.5 h-4 w-4 shrink-0',
                                status === 'completed' && 'text-brand',
                                status === 'in_progress' && 'text-brand',
                                status === 'pending' && 'text-slate-300',
                            )}
                            strokeWidth={1.8}
                        />
                        <div>
                            <p className="text-xs font-medium text-slate-800">{point.name}</p>
                            <p className="text-[11px] text-slate-500">
                                {status === 'completed' && formatDateTime(point.occurred_at)}
                                {status === 'in_progress' && 'In Progress'}
                                {status === 'pending' && 'Pending'}
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

export default function JourneyDetailsPanel({
    journey,
    canManage = false,
    processing = false,
    onClose,
    onStatusChange,
}) {
    const status = journeyStatusValue(journey.status);
    const distance = journey.distance_km
        ? `${journey.origin} → ${journey.destination} (${Number(journey.distance_km)} km)`
        : `${journey.origin} → ${journey.destination}`;

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[300px]">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Journey Details</h3>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>

            <div className="flex-1 overflow-y-auto px-4 py-4">
                <div className="flex items-start justify-between gap-2">
                    <p className="text-sm font-semibold text-brand">{journey.code}</p>
                    <JourneyStatusBadge status={status} label={journey.status_label} />
                </div>

                <div className="mt-3 space-y-0.5">
                    <DetailRow icon={User} label="Driver / Worker" value={journey.worker?.name} />
                    <DetailRow
                        icon={Truck}
                        label="Vehicle"
                        value={[journey.vehicle_plate, journey.vehicle_model].filter(Boolean).join(', ')}
                    />
                    <DetailRow icon={MapPin} label="Origin" value={journey.origin} />
                    <DetailRow icon={MapPin} label="Destination" value={journey.destination} />
                    <DetailRow icon={Route} label="Route Summary" value={distance} />
                    <DetailRow icon={MapPin} label="Journey Hub" value={journey.hub} />
                    <div className="flex gap-2.5 py-1.5">
                        <TriangleAlert className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" strokeWidth={1.8} />
                        <div>
                            <p className="text-[11px] text-slate-500">Risk Level</p>
                            <div className="mt-0.5">
                                <RiskMeter
                                    level={journey.risk_level}
                                    segments={journey.risk_segments}
                                    label={journey.risk_label}
                                />
                            </div>
                        </div>
                    </div>
                    <DetailRow
                        icon={CalendarDays}
                        label="Departure"
                        value={formatDateTime(journey.departure_at)}
                    />
                    <DetailRow
                        icon={CalendarCheck2}
                        label="ETA"
                        value={formatDateTime(journey.arrival_at)}
                    />
                    <DetailRow
                        icon={Phone}
                        label="Emergency Contact"
                        value={[journey.emergency_contact_name, journey.emergency_contact_phone]
                            .filter(Boolean)
                            .join(' · ')}
                    />
                </div>

                <div className="mt-4 border-t border-slate-100 pt-4">
                    <p className="mb-3 text-xs font-semibold text-slate-900">Checkpoint Progress</p>
                    <CheckpointTimeline checkpoints={journey.checkpoints} />
                </div>
            </div>

            <div className="space-y-2 border-t border-slate-100 p-3">
                <Link
                    href={route('journeys.show', journey.id)}
                    className="btn-primary flex w-full"
                >
                    View Full Details
                </Link>
                {canManage && (
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button
                                type="button"
                                disabled={processing}
                                className="btn-secondary flex w-full justify-between"
                            >
                                Update Status
                                <ChevronDown className="h-3.5 w-3.5" />
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content width="48" contentClasses="bg-white py-1">
                            {JOURNEY_STATUS_OPTIONS.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    disabled={option.value === status}
                                    onClick={() => onStatusChange(option.value)}
                                    className="flex w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 disabled:text-slate-400"
                                >
                                    {option.label}
                                </button>
                            ))}
                        </Dropdown.Content>
                    </Dropdown>
                )}
            </div>
        </aside>
    );
}
