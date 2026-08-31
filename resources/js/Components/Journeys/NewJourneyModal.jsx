import { useForm } from '@inertiajs/react';
import Input from '@/Components/Shared/Input';
import Modal from '@/Components/Shared/Modal';
import Select from '@/Components/Shared/Select';

export default function NewJourneyModal({ show, onClose, workers = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        worker_id: '',
        origin: '',
        destination: '',
        vehicle_plate: '',
        vehicle_model: '',
        hub: '',
        distance_km: '',
        departure_at: '',
        arrival_at: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        type: 'transfer',
    });

    const close = () => {
        reset();
        onClose();
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('journeys.store'), {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <Modal show={show} onClose={close} title="New Journey" maxWidth="2xl">
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-3 sm:grid-cols-2">
                    <Select
                        label="Driver / Worker"
                        value={data.worker_id}
                        onChange={(e) => setData('worker_id', e.target.value)}
                        error={errors.worker_id}
                        placeholder="Select worker"
                        options={workers.map((worker) => ({ value: worker.id, label: worker.name }))}
                    />
                    <Input
                        label="Origin"
                        value={data.origin}
                        onChange={(e) => setData('origin', e.target.value)}
                        error={errors.origin}
                    />
                    <Input
                        label="Destination"
                        value={data.destination}
                        onChange={(e) => setData('destination', e.target.value)}
                        error={errors.destination}
                    />
                    <Input
                        label="Vehicle Plate"
                        value={data.vehicle_plate}
                        onChange={(e) => setData('vehicle_plate', e.target.value)}
                        error={errors.vehicle_plate}
                    />
                    <Input
                        label="Vehicle Model"
                        value={data.vehicle_model}
                        onChange={(e) => setData('vehicle_model', e.target.value)}
                        error={errors.vehicle_model}
                    />
                    <Input
                        label="Journey Hub"
                        value={data.hub}
                        onChange={(e) => setData('hub', e.target.value)}
                        error={errors.hub}
                    />
                    <Input
                        label="Distance (km)"
                        type="number"
                        step="0.1"
                        value={data.distance_km}
                        onChange={(e) => setData('distance_km', e.target.value)}
                        error={errors.distance_km}
                    />
                    <Input
                        label="Departure"
                        type="datetime-local"
                        value={data.departure_at}
                        onChange={(e) => setData('departure_at', e.target.value)}
                        error={errors.departure_at}
                    />
                    <Input
                        label="ETA"
                        type="datetime-local"
                        value={data.arrival_at}
                        onChange={(e) => setData('arrival_at', e.target.value)}
                        error={errors.arrival_at}
                    />
                    <Input
                        label="Emergency Contact"
                        value={data.emergency_contact_name}
                        onChange={(e) => setData('emergency_contact_name', e.target.value)}
                        error={errors.emergency_contact_name}
                    />
                    <Input
                        label="Emergency Phone"
                        value={data.emergency_contact_phone}
                        onChange={(e) => setData('emergency_contact_phone', e.target.value)}
                        error={errors.emergency_contact_phone}
                    />
                </div>
                <p className="text-[11px] text-slate-500">
                    Risk is scored automatically on creation. Low-risk journeys are planned right
                    away; anything higher stays pending until a manager approves it.
                </p>
                <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onClick={close} className="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" disabled={processing} className="btn-primary">
                        {processing ? 'Creating...' : 'Create Journey'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
