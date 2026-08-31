import { Head, Link } from '@inertiajs/react';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import { JourneyStatusBadge, RiskMeter } from '@/Components/Journeys/journeyHelpers';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/utils/formatters';

export default function JourneysShow({ journey }) {
    return (
        <AppLayout title={journey.code || `Journey #${journey.id}`} subtitle="Journey details" showMeta={false}>
            <Head title={journey.code || 'Journey'} />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />
                <div className="card min-w-0 flex-1 p-5">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <Link href={route('journeys.index')} className="text-xs font-medium text-brand hover:underline">
                                Back to all journeys
                            </Link>
                            <h2 className="mt-1 text-lg font-semibold text-slate-900">{journey.code}</h2>
                        </div>
                        <JourneyStatusBadge status={journey.status} label={journey.status_label} />
                    </div>
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        {[
                            ['Driver / Worker', journey.worker?.name],
                            ['Vehicle', [journey.vehicle_plate, journey.vehicle_model].filter(Boolean).join(', ')],
                            ['Origin', journey.origin],
                            ['Destination', journey.destination],
                            ['Departure', formatDateTime(journey.departure_at)],
                            ['ETA', formatDateTime(journey.arrival_at)],
                            ['Journey Hub', journey.hub],
                            ['Approver', journey.approver?.name],
                            ['Emergency Contact', [journey.emergency_contact_name, journey.emergency_contact_phone].filter(Boolean).join(' · ')],
                        ].map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-slate-500">{label}</dt>
                                <dd className="mt-1 font-medium text-slate-900">{value || '—'}</dd>
                            </div>
                        ))}
                        <div>
                            <dt className="text-slate-500">Risk Level</dt>
                            <dd className="mt-1">
                                <RiskMeter
                                    level={journey.risk_level}
                                    segments={journey.risk_segments}
                                    label={journey.risk_label}
                                />
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </AppLayout>
    );
}
