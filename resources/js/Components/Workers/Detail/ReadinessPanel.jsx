import Badge from '@/Components/Shared/Badge';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatDate, statusLabel } from '@/utils/formatters';

const CATEGORIES = [
    ['medical_status', 'Medical'],
    ['certification_status', 'Certifications'],
    ['training_status', 'Training / LMS'],
    ['journey_status', 'Journey'],
    ['accommodation_status', 'Accommodation'],
    ['site_access_status', 'Site Access'],
];

export default function ReadinessPanel({ readiness }) {
    if (!readiness) {
        return (
            <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <EmptyState
                    title="No readiness check yet"
                    description="Run a readiness check from the Readiness dashboard to populate this worker's status."
                />
            </section>
        );
    }

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-[11px] font-semibold text-slate-900">Readiness Overview</h3>
                <div className="flex items-center gap-2">
                    <span className="text-[9px] text-slate-500">Overall</span>
                    <Badge status={readiness.overall_status} className="px-2 py-0.5 text-[9px]" />
                </div>
            </div>

            <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {CATEGORIES.map(([key, label]) => (
                    <div key={key} className="rounded-lg border border-slate-200 p-2.5">
                        <p className="text-[9px] uppercase tracking-wide text-slate-500">{label}</p>
                        <div className="mt-1.5">
                            <Badge status={readiness[key]} className="px-1.5 py-0.5 text-[8px]" />
                        </div>
                    </div>
                ))}
            </div>

            {readiness.notes && <p className="mt-3 text-[10px] text-slate-600">{readiness.notes}</p>}

            <p className="mt-3 text-[9px] text-slate-400">
                {readiness.last_checked_at
                    ? `Last checked ${formatDate(readiness.last_checked_at)}`
                    : 'This worker has not been checked yet.'}
                {readiness.overall_status && ` · ${statusLabel(readiness.overall_status)}`}
            </p>
        </section>
    );
}
