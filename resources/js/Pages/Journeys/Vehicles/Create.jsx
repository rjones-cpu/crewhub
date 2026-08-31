import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, FileText, Sparkles, Upload } from 'lucide-react';
import { useRef } from 'react';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import {
    Field,
    Section,
    SelectInput,
    TextArea,
    TextInput,
} from '@/Components/Journeys/Vehicles/FormPrimitives';
import RegistrationChecklist from '@/Components/Journeys/Vehicles/RegistrationChecklist';
import VehicleTypePicker from '@/Components/Journeys/Vehicles/VehicleTypePicker';
import ToggleSwitch from '@/Components/Shared/ToggleSwitch';
import AppLayout from '@/Layouts/AppLayout';

const MAKE_OPTIONS = [
    'Toyota',
    'Ford',
    'Nissan',
    'Isuzu',
    'Mitsubishi',
    'Volkswagen',
    'Land Rover',
    'Mercedes-Benz',
    'Hino',
    'Other',
].map((make) => ({ value: make, label: make }));

const COVERAGE_TYPE_OPTIONS = [
    { value: 'comprehensive', label: 'Comprehensive' },
    { value: 'third_party', label: 'Third Party' },
    { value: 'third_party_fire_theft', label: 'Third Party, Fire & Theft' },
    { value: 'fleet', label: 'Fleet Policy' },
];

const PURPOSE_OPTIONS = [
    { value: 'personnel_transport', label: 'Personnel Transport' },
    { value: 'material_transport', label: 'Material Transport' },
    { value: 'site_inspection', label: 'Site Inspection' },
    { value: 'emergency_response', label: 'Emergency Response' },
    { value: 'general', label: 'General Use' },
];

const TRANSMISSION_OPTIONS = [
    { value: 'manual', label: 'Manual' },
    { value: 'automatic', label: 'Automatic' },
];

const EQUIPMENT_OPTIONS = [
    'Satellite Phone',
    'First Aid Kit',
    'Fire Extinguisher',
    'Recovery Kit',
    'Spare Tyre',
    'Reflective Triangles',
    'GPS Tracker',
    'Roll Cage',
];

const CURRENT_YEAR = new Date().getFullYear();
const YEAR_OPTIONS = Array.from({ length: CURRENT_YEAR + 1 - 1989 }, (_, index) => {
    const year = CURRENT_YEAR + 1 - index;

    return { value: year, label: String(year) };
});

const MAX_DETAILS = 1000;

export default function RegisterVehicle({
    drivers = [],
    vehicleTypes = [],
    availabilityOptions = [],
}) {
    const fileInput = useRef(null);

    const { data, setData, post, processing, errors, transform } = useForm({
        make: '',
        model: '',
        year: '',
        vehicle_type: '',
        vin: '',
        assigned_driver_id: '',
        license_plate: '',

        has_attachments: true,
        insurance_document: null,
        policy_start_date: '',
        policy_end_date: '',
        insurance_provider: '',
        policy_number: '',
        coverage_type: '',
        coverage_amount: '',

        base_location: '',
        purpose: '',
        additional_notes: '',
        additional_details: '',

        availability: 'available',
        transmission: '',
        odometer_km: '',
        known_issues: '',

        equipment: [],
        maintenance_notes: '',
        last_service_at: '',
        next_service_due_at: '',

        is_draft: false,
    });

    const filled = (...keys) => keys.every((key) => {
        const value = data[key];

        return Array.isArray(value) ? value.length > 0 : String(value ?? '').trim() !== '';
    });

    const checklist = [
        {
            title: 'Vehicle Information',
            description: 'Make, model, year, VIN, and driver',
            complete: filled('make', 'model', 'year', 'vin', 'license_plate', 'assigned_driver_id'),
        },
        {
            title: 'Insurance / Registration',
            description: 'Insurance documents and policy details',
            complete: filled(
                'insurance_provider',
                'policy_number',
                'coverage_type',
                'coverage_amount',
                'policy_start_date',
                'policy_end_date',
            ),
        },
        {
            title: 'Vehicle Type',
            description: 'Select the correct vehicle category',
            complete: filled('vehicle_type'),
        },
        {
            title: 'Additional Information',
            description: 'Purpose, base location, and notes',
            complete: filled('base_location', 'purpose'),
        },
        {
            title: 'Vehicle Status / Configuration',
            description: 'Availability, transmission, KM, and issues',
            complete: filled('availability', 'transmission', 'odometer_km'),
        },
        {
            title: 'Maintenance Requirements',
            description: 'Maintenance and equipment details',
            complete: filled('equipment'),
        },
    ];

    const toggleEquipment = (item) => {
        setData(
            'equipment',
            data.equipment.includes(item)
                ? data.equipment.filter((value) => value !== item)
                : [...data.equipment, item],
        );
    };

    const submit = (event, asDraft = false) => {
        event.preventDefault();
        transform((payload) => ({ ...payload, is_draft: asDraft }));
        post(route('journeys.vehicles.store'), { forceFormData: true, preserveScroll: true });
    };

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Register a Vehicle" />

            <form onSubmit={submit} className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="min-w-0 flex-1 space-y-4">
                    <div>
                        <h1 className="text-lg font-semibold text-slate-900">Register a Vehicle</h1>
                        <p className="mt-0.5 text-xs text-slate-500">
                            Enter and manage vehicle details for journey approval.
                        </p>
                    </div>

                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div className="min-w-0 flex-1 space-y-4">
                            <Section index={1} title="Vehicle Information">
                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <Field label="Make" required error={errors.make} htmlFor="make">
                                        <SelectInput
                                            id="make"
                                            value={data.make}
                                            onChange={(e) => setData('make', e.target.value)}
                                            error={errors.make}
                                            placeholder="Select make"
                                            options={MAKE_OPTIONS}
                                        />
                                    </Field>
                                    <Field label="Model" required error={errors.model} htmlFor="model">
                                        <TextInput
                                            id="model"
                                            value={data.model}
                                            onChange={(e) => setData('model', e.target.value)}
                                            error={errors.model}
                                            placeholder="Enter model"
                                        />
                                    </Field>
                                    <Field label="Year" required error={errors.year} htmlFor="year">
                                        <SelectInput
                                            id="year"
                                            value={data.year}
                                            onChange={(e) => setData('year', e.target.value)}
                                            error={errors.year}
                                            placeholder="Select year"
                                            options={YEAR_OPTIONS}
                                        />
                                    </Field>
                                    <Field
                                        label="Vehicle Type"
                                        required
                                        error={errors.vehicle_type}
                                        htmlFor="vehicle_type"
                                    >
                                        <SelectInput
                                            id="vehicle_type"
                                            value={data.vehicle_type}
                                            onChange={(e) => setData('vehicle_type', e.target.value)}
                                            error={errors.vehicle_type}
                                            placeholder="Select type"
                                            options={vehicleTypes}
                                        />
                                    </Field>
                                </div>

                                <div className="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    <Field
                                        label="VIN Number (Vehicle Identification Number)"
                                        required
                                        error={errors.vin}
                                        htmlFor="vin"
                                    >
                                        <TextInput
                                            id="vin"
                                            value={data.vin}
                                            onChange={(e) => setData('vin', e.target.value)}
                                            error={errors.vin}
                                            placeholder="Enter VIN"
                                        />
                                    </Field>
                                    <Field
                                        label="Assigned Driver"
                                        required
                                        error={errors.assigned_driver_id}
                                        htmlFor="assigned_driver_id"
                                    >
                                        <SelectInput
                                            id="assigned_driver_id"
                                            value={data.assigned_driver_id}
                                            onChange={(e) => setData('assigned_driver_id', e.target.value)}
                                            error={errors.assigned_driver_id}
                                            placeholder="Select driver"
                                            options={drivers.map((driver) => ({
                                                value: driver.id,
                                                label: driver.name,
                                            }))}
                                        />
                                    </Field>
                                    <Field
                                        label="License Plate"
                                        required
                                        error={errors.license_plate}
                                        htmlFor="license_plate"
                                    >
                                        <TextInput
                                            id="license_plate"
                                            value={data.license_plate}
                                            onChange={(e) => setData('license_plate', e.target.value)}
                                            error={errors.license_plate}
                                            placeholder="Enter license plate"
                                        />
                                    </Field>
                                </div>
                            </Section>

                            <div className="grid gap-4 xl:grid-cols-2">
                                <Section index={2} title="Insurance / Registration">
                                    <div className="flex items-center gap-2">
                                        <ToggleSwitch
                                            size="sm"
                                            checked={data.has_attachments}
                                            onChange={(value) => setData('has_attachments', value)}
                                            label="Upload attachments"
                                        />
                                        <span className="text-[11px] text-slate-700">Upload attachments</span>
                                    </div>

                                    {data.has_attachments && (
                                        <div className="mt-3">
                                            <Field
                                                label="Insurance / Registration Document"
                                                required
                                                error={errors.insurance_document}
                                            >
                                                <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 px-3 py-5 text-center">
                                                    <FileText
                                                        className="mx-auto h-5 w-5 text-slate-400"
                                                        strokeWidth={1.6}
                                                    />
                                                    <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
                                                        <span className="text-[11px] text-slate-500">
                                                            {data.insurance_document
                                                                ? data.insurance_document.name
                                                                : 'Drag and drop file here or'}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => fileInput.current?.click()}
                                                            className="btn-primary min-h-7 px-2.5 text-[11px]"
                                                        >
                                                            <Upload className="h-3 w-3" />
                                                            Upload File
                                                        </button>
                                                    </div>
                                                    <p className="mt-2 text-[11px] text-slate-400">
                                                        Accepted formats: PDF, JPG, PNG (Max 10MB)
                                                    </p>
                                                    <input
                                                        ref={fileInput}
                                                        type="file"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        className="hidden"
                                                        onChange={(e) =>
                                                            setData('insurance_document', e.target.files[0] ?? null)
                                                        }
                                                    />
                                                </div>
                                            </Field>
                                        </div>
                                    )}

                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        <Field
                                            label="Policy Start Date"
                                            required
                                            error={errors.policy_start_date}
                                            htmlFor="policy_start_date"
                                        >
                                            <TextInput
                                                id="policy_start_date"
                                                type="date"
                                                value={data.policy_start_date}
                                                onChange={(e) => setData('policy_start_date', e.target.value)}
                                                error={errors.policy_start_date}
                                            />
                                        </Field>
                                        <Field
                                            label="Policy End Date"
                                            required
                                            error={errors.policy_end_date}
                                            htmlFor="policy_end_date"
                                        >
                                            <TextInput
                                                id="policy_end_date"
                                                type="date"
                                                value={data.policy_end_date}
                                                onChange={(e) => setData('policy_end_date', e.target.value)}
                                                error={errors.policy_end_date}
                                            />
                                        </Field>
                                        <Field
                                            label="Insurance Provider"
                                            required
                                            error={errors.insurance_provider}
                                            htmlFor="insurance_provider"
                                        >
                                            <TextInput
                                                id="insurance_provider"
                                                value={data.insurance_provider}
                                                onChange={(e) => setData('insurance_provider', e.target.value)}
                                                error={errors.insurance_provider}
                                                placeholder="Enter insurance provider"
                                            />
                                        </Field>
                                        <Field
                                            label="Policy Number"
                                            required
                                            error={errors.policy_number}
                                            htmlFor="policy_number"
                                        >
                                            <TextInput
                                                id="policy_number"
                                                value={data.policy_number}
                                                onChange={(e) => setData('policy_number', e.target.value)}
                                                error={errors.policy_number}
                                                placeholder="Enter policy number"
                                            />
                                        </Field>
                                        <Field
                                            label="Coverage Type"
                                            required
                                            error={errors.coverage_type}
                                            htmlFor="coverage_type"
                                        >
                                            <SelectInput
                                                id="coverage_type"
                                                value={data.coverage_type}
                                                onChange={(e) => setData('coverage_type', e.target.value)}
                                                error={errors.coverage_type}
                                                placeholder="Select coverage type"
                                                options={COVERAGE_TYPE_OPTIONS}
                                            />
                                        </Field>
                                        <Field
                                            label="Coverage Amount"
                                            required
                                            error={errors.coverage_amount}
                                            htmlFor="coverage_amount"
                                        >
                                            <TextInput
                                                id="coverage_amount"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={data.coverage_amount}
                                                onChange={(e) => setData('coverage_amount', e.target.value)}
                                                error={errors.coverage_amount}
                                                placeholder="e.g., 2000000"
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                <div className="space-y-4">
                                    <Section index={3} title="Vehicle Type">
                                        <VehicleTypePicker
                                            options={vehicleTypes}
                                            value={data.vehicle_type}
                                            onChange={(value) => setData('vehicle_type', value)}
                                            error={errors.vehicle_type}
                                        />
                                    </Section>

                                    <Section index={4} title="Additional Information">
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <Field
                                                label="Base Location"
                                                error={errors.base_location}
                                                htmlFor="base_location"
                                            >
                                                <TextInput
                                                    id="base_location"
                                                    value={data.base_location}
                                                    onChange={(e) => setData('base_location', e.target.value)}
                                                    error={errors.base_location}
                                                    placeholder="Select base location"
                                                />
                                            </Field>
                                            <Field
                                                label="Additional Notes"
                                                error={errors.additional_notes}
                                                htmlFor="additional_notes"
                                                className="sm:row-span-2"
                                            >
                                                <TextArea
                                                    id="additional_notes"
                                                    rows={4}
                                                    value={data.additional_notes}
                                                    onChange={(e) => setData('additional_notes', e.target.value)}
                                                    error={errors.additional_notes}
                                                    placeholder="Enter any additional notes"
                                                />
                                            </Field>
                                            <Field
                                                label="Purpose of Vehicle"
                                                error={errors.purpose}
                                                htmlFor="purpose"
                                            >
                                                <SelectInput
                                                    id="purpose"
                                                    value={data.purpose}
                                                    onChange={(e) => setData('purpose', e.target.value)}
                                                    error={errors.purpose}
                                                    placeholder="Select purpose of vehicle"
                                                    options={PURPOSE_OPTIONS}
                                                />
                                            </Field>
                                        </div>

                                        <div className="mt-3 flex gap-2 rounded-lg bg-brand-soft px-3 py-2.5">
                                            <Sparkles
                                                className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand"
                                                strokeWidth={1.8}
                                            />
                                            <div>
                                                <p className="text-[11px] font-medium text-brand">
                                                    Your AI assistant is available to help
                                                </p>
                                                <p className="text-[11px] text-brand/80">
                                                    Smart auto-complete and guidance powered by AI.
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-3">
                                            <Field
                                                label="Additional Details"
                                                error={errors.additional_details}
                                                htmlFor="additional_details"
                                            >
                                                <TextArea
                                                    id="additional_details"
                                                    rows={3}
                                                    maxLength={MAX_DETAILS}
                                                    value={data.additional_details}
                                                    onChange={(e) => setData('additional_details', e.target.value)}
                                                    error={errors.additional_details}
                                                    placeholder="Enter additional details about the vehicle, usage, or any special requirements..."
                                                />
                                            </Field>
                                            <p className="mt-1 text-[11px] text-slate-400">
                                                {data.additional_details.length} / {MAX_DETAILS} characters
                                            </p>
                                        </div>
                                    </Section>
                                </div>
                            </div>

                            <div className="grid gap-4 xl:grid-cols-2">
                                <Section index={5} title="Vehicle Status / Configuration">
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <Field
                                            label="Availability"
                                            error={errors.availability}
                                            htmlFor="availability"
                                        >
                                            <SelectInput
                                                id="availability"
                                                value={data.availability}
                                                onChange={(e) => setData('availability', e.target.value)}
                                                error={errors.availability}
                                                options={availabilityOptions}
                                            />
                                        </Field>
                                        <Field
                                            label="Transmission"
                                            error={errors.transmission}
                                            htmlFor="transmission"
                                        >
                                            <SelectInput
                                                id="transmission"
                                                value={data.transmission}
                                                onChange={(e) => setData('transmission', e.target.value)}
                                                error={errors.transmission}
                                                placeholder="Select transmission"
                                                options={TRANSMISSION_OPTIONS}
                                            />
                                        </Field>
                                        <Field
                                            label="Odometer (km)"
                                            error={errors.odometer_km}
                                            htmlFor="odometer_km"
                                        >
                                            <TextInput
                                                id="odometer_km"
                                                type="number"
                                                min="0"
                                                value={data.odometer_km}
                                                onChange={(e) => setData('odometer_km', e.target.value)}
                                                error={errors.odometer_km}
                                                placeholder="e.g., 84000"
                                            />
                                        </Field>
                                        <Field
                                            label="Known Issues"
                                            error={errors.known_issues}
                                            htmlFor="known_issues"
                                        >
                                            <TextInput
                                                id="known_issues"
                                                value={data.known_issues}
                                                onChange={(e) => setData('known_issues', e.target.value)}
                                                error={errors.known_issues}
                                                placeholder="None"
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                <Section index={6} title="Maintenance Requirements">
                                    <p className="text-[11px] font-medium text-slate-700">
                                        On-board Equipment
                                    </p>
                                    <div className="mt-2 grid grid-cols-2 gap-x-3 gap-y-2">
                                        {EQUIPMENT_OPTIONS.map((item) => (
                                            <label
                                                key={item}
                                                className="flex cursor-pointer items-center gap-2 text-[11px] text-slate-700"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={data.equipment.includes(item)}
                                                    onChange={() => toggleEquipment(item)}
                                                    className="h-3.5 w-3.5 rounded border-slate-300 text-brand focus:ring-brand"
                                                />
                                                {item}
                                            </label>
                                        ))}
                                    </div>

                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        <Field
                                            label="Last Service Date"
                                            error={errors.last_service_at}
                                            htmlFor="last_service_at"
                                        >
                                            <TextInput
                                                id="last_service_at"
                                                type="date"
                                                value={data.last_service_at}
                                                onChange={(e) => setData('last_service_at', e.target.value)}
                                                error={errors.last_service_at}
                                            />
                                        </Field>
                                        <Field
                                            label="Next Service Due"
                                            error={errors.next_service_due_at}
                                            htmlFor="next_service_due_at"
                                        >
                                            <TextInput
                                                id="next_service_due_at"
                                                type="date"
                                                value={data.next_service_due_at}
                                                onChange={(e) => setData('next_service_due_at', e.target.value)}
                                                error={errors.next_service_due_at}
                                            />
                                        </Field>
                                    </div>

                                    <div className="mt-3">
                                        <Field
                                            label="Maintenance Notes"
                                            error={errors.maintenance_notes}
                                            htmlFor="maintenance_notes"
                                        >
                                            <TextArea
                                                id="maintenance_notes"
                                                rows={3}
                                                value={data.maintenance_notes}
                                                onChange={(e) => setData('maintenance_notes', e.target.value)}
                                                error={errors.maintenance_notes}
                                                placeholder="Service intervals, recurring faults, or special maintenance requirements"
                                            />
                                        </Field>
                                    </div>
                                </Section>
                            </div>
                        </div>

                        <RegistrationChecklist items={checklist} />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                        <Link href={route('journeys.vehicles')} className="btn-secondary min-h-9 px-4 text-xs">
                            Cancel
                        </Link>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                disabled={processing}
                                onClick={(event) => submit(event, true)}
                                className="btn-secondary min-h-9 px-4 text-xs"
                            >
                                Save Draft
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-primary min-h-9 px-4 text-xs"
                            >
                                {processing ? 'Saving...' : 'Register Vehicle'}
                                <ArrowRight className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
