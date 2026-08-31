import { router } from '@inertiajs/react';
import {
    CalendarClock,
    CircleUser,
    Flag,
    MapPin,
    Navigation,
    RefreshCw,
    ShieldAlert,
    Truck,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { formatDateTime } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import { RISK_TEXT_CLASSES, RiskPill, ScoreBar } from './riskHelpers';

function DetailRow({ icon: Icon, label, value }) {
    return (
        <div className="flex items-center justify-between gap-3 py-1">
            <span className="inline-flex items-center gap-2 text-[11px] text-slate-500">
                <Icon className="h-3.5 w-3.5 shrink-0 text-slate-400" strokeWidth={1.8} />
                {label}
            </span>
            <span className="min-w-0 truncate text-right text-[11px] font-medium text-slate-800">
                {value || '—'}
            </span>
        </div>
    );
}

export default function RiskDetailsPanel({ assessment, canManage = false, onClose }) {
    const journey = assessment.journey || {};
    const [applied, setApplied] = useState([]);
    const [recalculating, setRecalculating] = useState(false);

    useEffect(() => {
        setApplied(assessment.recommendations || []);
    }, [assessment.id, assessment.recommendations]);

    const toggleRecommendation = (item) => {
        setApplied((current) =>
            current.includes(item)
                ? current.filter((value) => value !== item)
                : [...current, item],
        );
    };

    const recalculate = () => {
        setRecalculating(true);
        router.post(
            route('journeys.risk.recalculate', assessment.id),
            {},
            { preserveScroll: true, onFinish: () => setRecalculating(false) },
        );
    };

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[290px]">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Risk Assessment Details</h3>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close"
                    className="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>

            <div className="flex-1 overflow-y-auto px-4 py-4">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <p className="text-[11px] text-slate-500">Assessment ID</p>
                        <p className="mt-0.5 text-xs font-semibold text-brand">{assessment.code}</p>
                    </div>
                    <div>
                        <p className="text-[11px] text-slate-500">Journey ID</p>
                        <p className="mt-0.5 text-xs font-semibold text-brand">{journey.code || '—'}</p>
                    </div>
                </div>

                <div className="mt-3 space-y-0.5 border-t border-slate-100 pt-3">
                    <DetailRow icon={CircleUser} label="Driver / Worker" value={journey.worker?.name} />
                    <DetailRow
                        icon={Truck}
                        label="Vehicle"
                        value={
                            journey.vehicle?.plate
                                ? `${journey.vehicle.name || ''} (${journey.vehicle.plate})`.trim()
                                : journey.vehicle?.name
                        }
                    />
                    <DetailRow icon={MapPin} label="Origin" value={journey.origin} />
                    <DetailRow icon={Flag} label="Destination" value={journey.destination} />
                    <DetailRow icon={Navigation} label="Journey Hub" value={journey.hub} />
                    <DetailRow
                        icon={CalendarClock}
                        label="Departure"
                        value={formatDateTime(journey.departure_at)}
                    />
                    <DetailRow icon={CalendarClock} label="ETA" value={formatDateTime(journey.arrival_at)} />
                </div>

                <div className="mt-4 border-t border-slate-100 pt-4">
                    <p className="text-[11px] font-medium text-slate-700">Overall Risk Score</p>
                    <div className="mt-2 flex items-center justify-between gap-3">
                        <p className="text-3xl font-semibold text-slate-900">
                            {assessment.score}
                            <span className="ml-1 text-xs font-normal text-slate-400">/100</span>
                        </p>
                        <span className="inline-flex items-center gap-1.5">
                            <ShieldAlert
                                className={cn('h-4 w-4', RISK_TEXT_CLASSES[assessment.outcome])}
                                strokeWidth={1.8}
                            />
                            <RiskPill level={assessment.outcome} label={assessment.outcome_label} />
                        </span>
                    </div>
                    <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-gradient-to-r from-success via-amber-400 to-danger">
                        <div
                            className="h-full border-r-2 border-slate-900/70"
                            style={{ width: `${Math.min(100, Math.max(0, assessment.score))}%` }}
                        />
                    </div>
                </div>

                {assessment.factors?.length > 0 && (
                    <div className="mt-4 border-t border-slate-100 pt-4">
                        <p className="text-[11px] font-semibold text-slate-900">Risk Factors</p>
                        <ul className="mt-2 space-y-2">
                            {assessment.factors.map((factor) => (
                                <li key={factor.key} className="flex items-center gap-2">
                                    <span className="w-[104px] shrink-0 truncate text-[11px] text-slate-600">
                                        {factor.label}
                                    </span>
                                    <ScoreBar
                                        score={factor.score}
                                        level={factor.level}
                                        showValue={false}
                                        className="flex-1"
                                    />
                                    <span className="w-12 shrink-0 text-right text-[11px] text-slate-500">
                                        {factor.score}/100
                                    </span>
                                    <span
                                        className={cn(
                                            'w-12 shrink-0 text-right text-[11px] font-medium capitalize',
                                            RISK_TEXT_CLASSES[factor.level],
                                        )}
                                    >
                                        {factor.level}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {assessment.recommendations?.length > 0 && (
                    <div className="mt-4 border-t border-slate-100 pt-4">
                        <p className="text-[11px] font-semibold text-slate-900">
                            Risk Mitigation Recommendations
                        </p>
                        <ul className="mt-2 space-y-2">
                            {assessment.recommendations.map((item) => (
                                <li key={item}>
                                    <label className="flex cursor-pointer items-start gap-2 text-[11px] leading-snug text-slate-700">
                                        <input
                                            type="checkbox"
                                            checked={applied.includes(item)}
                                            onChange={() => toggleRecommendation(item)}
                                            className="mt-0.5 h-3.5 w-3.5 rounded border-slate-300 text-brand focus:ring-brand"
                                        />
                                        {item}
                                    </label>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            <div className="flex items-center gap-2 border-t border-slate-100 p-3">
                <a
                    href={journey.id ? route('journeys.show', journey.id) : '#'}
                    className="flex-1 rounded-lg border border-brand/40 bg-white px-3 py-2 text-center text-[11px] font-medium text-brand transition hover:bg-brand-soft"
                >
                    View Full Assessment
                </a>
                {canManage && (
                    <button
                        type="button"
                        onClick={recalculate}
                        disabled={recalculating}
                        className="btn-primary flex-1 justify-center px-3 py-2 text-[11px]"
                    >
                        <RefreshCw className={cn('h-3 w-3', recalculating && 'animate-spin')} />
                        {recalculating ? 'Working...' : 'Recalculate Risk'}
                    </button>
                )}
            </div>
        </aside>
    );
}
