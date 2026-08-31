import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Shared/Modal';
import Select from '@/Components/Shared/Select';
import Input from '@/Components/Shared/Input';

const WEATHER_OPTIONS = [
    'Clear',
    'Overcast',
    'Windy',
    'Fog',
    'Dusty',
    'Light Rain',
    'Heavy Rain',
].map((value) => ({ value, label: value }));

const ROAD_OPTIONS = ['Dry', 'Wet', 'Gravel', 'Poor', 'Mud / Slippery'].map((value) => ({
    value,
    label: value,
}));

const QUALITY_OPTIONS = ['Good', 'Fair', 'Poor'].map((value) => ({ value, label: value }));

export default function NewAssessmentModal({ show, onClose, journeys = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        journey_id: '',
        weather: '',
        temperature_c: '',
        road_conditions: '',
        road_condition_quality: '',
    });

    const close = () => {
        reset();
        onClose();
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('journeys.risk.store'), { preserveScroll: true, onSuccess: close });
    };

    return (
        <Modal show={show} onClose={close} title="New Risk Assessment" maxWidth="lg">
            <form onSubmit={submit} className="space-y-4">
                <Select
                    label="Journey"
                    value={data.journey_id}
                    onChange={(e) => setData('journey_id', e.target.value)}
                    error={errors.journey_id}
                    placeholder="Select journey"
                    options={journeys.map((journey) => ({
                        value: journey.id,
                        label: journey.label,
                    }))}
                />

                <p className="text-[11px] text-slate-500">
                    Conditions below are snapshotted onto the assessment. Anything left blank falls
                    back to the driver&apos;s journey answers.
                </p>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Select
                        label="Weather"
                        value={data.weather}
                        onChange={(e) => setData('weather', e.target.value)}
                        error={errors.weather}
                        placeholder="Use journey answer"
                        options={WEATHER_OPTIONS}
                    />
                    <Input
                        label="Temperature (°C)"
                        type="number"
                        value={data.temperature_c}
                        onChange={(e) => setData('temperature_c', e.target.value)}
                        error={errors.temperature_c}
                    />
                    <Select
                        label="Road Conditions"
                        value={data.road_conditions}
                        onChange={(e) => setData('road_conditions', e.target.value)}
                        error={errors.road_conditions}
                        placeholder="Use journey answer"
                        options={ROAD_OPTIONS}
                    />
                    <Select
                        label="Road Quality"
                        value={data.road_condition_quality}
                        onChange={(e) => setData('road_condition_quality', e.target.value)}
                        error={errors.road_condition_quality}
                        placeholder="Not specified"
                        options={QUALITY_OPTIONS}
                    />
                </div>

                <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onClick={close} className="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" disabled={processing} className="btn-primary">
                        {processing ? 'Calculating...' : 'Calculate Risk'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
