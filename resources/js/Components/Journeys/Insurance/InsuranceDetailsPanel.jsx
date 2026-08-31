import { useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CircleUser,
    FileText,
    ShieldCheck,
    Truck,
    X,
} from 'lucide-react';
import { useEffect } from 'react';
import { Field, TextArea } from '@/Components/Journeys/Vehicles/FormPrimitives';
import { formatDate, formatDateTime } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import { CoverBadge } from './insuranceHelpers';

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

export default function InsuranceDetailsPanel({ vehicle, canManage = false, onClose }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        status: vehicle.insurance_status || 'unverified',
        notes: vehicle.insurance_verification_notes || '',
    });

    useEffect(() => {
        setData({
            status: vehicle.insurance_status || 'unverified',
            notes: vehicle.insurance_verification_notes || '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [vehicle.id]);

    const decide = (status) => {
        transform((payload) => ({ ...payload, status }));
        post(route('journeys.insurance.confirm', vehicle.id), { preserveScroll: true });
    };

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[290px]">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">Insurance Details</h3>
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
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-slate-900">{vehicle.display_name}</p>
                        <p className="mt-0.5 text-[11px] text-slate-500">
                            {vehicle.license_plate} · {vehicle.vehicle_type_label}
                        </p>
                    </div>
                    <CoverBadge vehicle={vehicle} />
                </div>

                <div className="mt-3 space-y-0.5 border-t border-slate-100 pt-3">
                    <DetailRow icon={ShieldCheck} label="Provider" value={vehicle.insurance_provider} />
                    <DetailRow icon={FileText} label="Policy Number" value={vehicle.policy_number} />
                    <DetailRow icon={ShieldCheck} label="Coverage" value={vehicle.coverage_type} />
                    <DetailRow
                        icon={ShieldCheck}
                        label="Amount"
                        value={vehicle.coverage_amount ? `R ${vehicle.coverage_amount}` : null}
                    />
                    <DetailRow
                        icon={CalendarDays}
                        label="Valid From"
                        value={formatDate(vehicle.policy_start_date)}
                    />
                    <DetailRow
                        icon={CalendarDays}
                        label="Valid Until"
                        value={formatDate(vehicle.policy_end_date)}
                    />
                    <DetailRow icon={Truck} label="Driver" value={vehicle.assigned_driver?.name} />
                </div>

                {vehicle.insurance_document_url && (
                    <a
                        href={vehicle.insurance_document_url}
                        target="_blank"
                        rel="noreferrer"
                        className="mt-3 flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-[11px] font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <FileText className="h-3.5 w-3.5" />
                        View policy document
                    </a>
                )}

                <div className="mt-4 border-t border-slate-100 pt-4">
                    <p className="text-[11px] font-semibold text-slate-900">Confirmation</p>
                    <div className="mt-2 space-y-0.5">
                        <DetailRow
                            icon={ShieldCheck}
                            label="Status"
                            value={vehicle.insurance_status_label}
                        />
                        <DetailRow
                            icon={CircleUser}
                            label="Checked By"
                            value={vehicle.insurance_verifier?.name}
                        />
                        <DetailRow
                            icon={CalendarDays}
                            label="Checked At"
                            value={formatDateTime(vehicle.insurance_verified_at)}
                        />
                    </div>

                    {canManage && (
                        <div className="mt-3">
                            <Field label="Notes" error={errors.notes} htmlFor="notes">
                                <TextArea
                                    id="notes"
                                    rows={2}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    error={errors.notes}
                                    placeholder="Reason for flagging, or what was verified"
                                />
                            </Field>
                        </div>
                    )}
                </div>
            </div>

            {canManage && (
                <div className="flex items-center gap-2 border-t border-slate-100 p-3">
                    <button
                        type="button"
                        onClick={() => decide('flagged')}
                        disabled={processing}
                        className={cn(
                            'flex-1 rounded-lg border border-danger/40 bg-white px-3 py-2 text-[11px] font-medium text-danger transition hover:bg-danger-soft',
                        )}
                    >
                        Flag Issue
                    </button>
                    <button
                        type="button"
                        onClick={() => decide('confirmed')}
                        disabled={processing || !vehicle.insurance_valid}
                        title={vehicle.insurance_valid ? undefined : 'Policy has expired'}
                        className="btn-primary flex-1 justify-center px-3 py-2 text-[11px] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Confirm Cover'}
                    </button>
                </div>
            )}
        </aside>
    );
}
