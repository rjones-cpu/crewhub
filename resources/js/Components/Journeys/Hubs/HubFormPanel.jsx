import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { Field, TextArea, TextInput } from '@/Components/Journeys/Vehicles/FormPrimitives';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';

const EMPTY = {
    name: '',
    code: '',
    location: '',
    latitude: '',
    longitude: '',
    radius_km: 50,
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    notes: '',
    is_active: true,
};

function toFormState(hub) {
    if (!hub) {
        return EMPTY;
    }

    return {
        name: hub.name || '',
        code: hub.code || '',
        location: hub.location || '',
        latitude: hub.latitude ?? '',
        longitude: hub.longitude ?? '',
        radius_km: hub.radius_km ?? 50,
        contact_name: hub.contact_name || '',
        contact_phone: hub.contact_phone || '',
        contact_email: hub.contact_email || '',
        notes: hub.notes || '',
        is_active: Boolean(hub.is_active),
    };
}

export default function HubFormPanel({ hub, onCancel, onSaved }) {
    const editing = Boolean(hub);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(
        toFormState(hub),
    );

    useEffect(() => {
        clearErrors();
        setData(toFormState(hub));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [hub?.id]);

    const submit = (event) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSaved?.();
            },
        };

        if (editing) {
            put(route('journeys.hubs.update', hub.id), options);
        } else {
            post(route('journeys.hubs.store'), options);
        }
    };

    return (
        <aside className="card w-full shrink-0 p-4 lg:sticky lg:top-24 lg:w-[280px]">
            <h2 className="text-sm font-semibold text-slate-900">
                {editing ? 'Edit Journey Hub' : 'Add Journey Hub'}
            </h2>
            <p className="mt-0.5 text-[11px] text-slate-500">
                Hubs coordinate departures and receive checkpoint call-ins.
            </p>

            <form onSubmit={submit} className="mt-4 space-y-3">
                <Field label="Hub Name" required error={errors.name} htmlFor="name">
                    <TextInput
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="e.g. Rustenburg Hub"
                    />
                </Field>

                <Field label="Hub Code" required error={errors.code} htmlFor="code">
                    <TextInput
                        id="code"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                        error={errors.code}
                        placeholder="e.g. RBG"
                    />
                </Field>

                <Field label="Location" error={errors.location} htmlFor="location">
                    <TextInput
                        id="location"
                        value={data.location}
                        onChange={(e) => setData('location', e.target.value)}
                        error={errors.location}
                        placeholder="Street or site address"
                    />
                </Field>

                <div className="grid grid-cols-2 gap-2">
                    <Field label="Latitude" error={errors.latitude} htmlFor="latitude">
                        <TextInput
                            id="latitude"
                            type="number"
                            step="0.0000001"
                            value={data.latitude}
                            onChange={(e) => setData('latitude', e.target.value)}
                            error={errors.latitude}
                        />
                    </Field>
                    <Field label="Longitude" error={errors.longitude} htmlFor="longitude">
                        <TextInput
                            id="longitude"
                            type="number"
                            step="0.0000001"
                            value={data.longitude}
                            onChange={(e) => setData('longitude', e.target.value)}
                            error={errors.longitude}
                        />
                    </Field>
                </div>

                <Field
                    label="Coverage Radius (km)"
                    error={errors.radius_km}
                    hint="Area this hub is responsible for"
                    htmlFor="radius_km"
                >
                    <TextInput
                        id="radius_km"
                        type="number"
                        min="1"
                        value={data.radius_km}
                        onChange={(e) => setData('radius_km', e.target.value)}
                        error={errors.radius_km}
                    />
                </Field>

                <Field label="Contact Name" error={errors.contact_name} htmlFor="contact_name">
                    <TextInput
                        id="contact_name"
                        value={data.contact_name}
                        onChange={(e) => setData('contact_name', e.target.value)}
                        error={errors.contact_name}
                    />
                </Field>

                <div className="grid grid-cols-2 gap-2">
                    <Field label="Phone" error={errors.contact_phone} htmlFor="contact_phone">
                        <TextInput
                            id="contact_phone"
                            value={data.contact_phone}
                            onChange={(e) => setData('contact_phone', e.target.value)}
                            error={errors.contact_phone}
                        />
                    </Field>
                    <Field label="Email" error={errors.contact_email} htmlFor="contact_email">
                        <TextInput
                            id="contact_email"
                            type="email"
                            value={data.contact_email}
                            onChange={(e) => setData('contact_email', e.target.value)}
                            error={errors.contact_email}
                        />
                    </Field>
                </div>

                <Field label="Notes" error={errors.notes} htmlFor="notes">
                    <TextArea
                        id="notes"
                        rows={2}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        error={errors.notes}
                        placeholder="Call-in procedure, operating hours, or escalation contacts"
                    />
                </Field>

                <div className="flex items-center justify-between gap-2">
                    <span className="text-[11px] font-medium text-slate-700">Active</span>
                    <ToggleSwitch
                        size="sm"
                        checked={data.is_active}
                        onChange={(value) => setData('is_active', value)}
                        label="Hub active"
                    />
                </div>

                <div className="space-y-2 pt-1">
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn-primary w-full justify-center py-2 text-xs"
                    >
                        {processing ? 'Saving...' : editing ? 'Save Changes' : 'Create Hub'}
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            reset();
                            onCancel?.();
                        }}
                        className="w-full rounded-lg px-3 py-2 text-center text-xs font-medium text-slate-500 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </aside>
    );
}
