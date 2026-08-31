import { useForm } from '@inertiajs/react';
import { CalendarDays, ChevronDown, Save, Upload, X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import SearchableSelect from '@/Components/Shared/SearchableSelect';

// Height comes from padding rather than a fixed h-8 so inputs and selects resolve to
// the same box: native select chrome ignores a fixed height and pushes its text down.
const inputClass =
    'block w-full rounded border border-slate-200 bg-white px-2.5 py-[7px] text-[10px] leading-4 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';

const selectClass = `${inputClass} cursor-pointer appearance-none pr-7`;

// h-full + mt-auto keeps controls on one baseline across a row even when a longer
// label wraps to a second line.
function Field({ label, required = false, error, children, className = '' }) {
    return (
        <label className={`flex h-full flex-col ${className}`}>
            <span className="mb-1 block text-[9px] font-medium text-slate-700">
                {label} {required && <span className="text-rose-500">*</span>}
            </span>
            <span className="mt-auto block">{children}</span>
            {error && <span className="mt-1 block text-[8px] text-rose-600">{error}</span>}
        </label>
    );
}

function SelectField({ value, onChange, disabled = false, children }) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={onChange}
                disabled={disabled}
                className={`${selectClass} ${disabled ? 'bg-slate-50 text-slate-500' : ''}`}
            >
                {children}
            </select>
            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
        </div>
    );
}

function DateField({ value, onChange, name }) {
    const inputRef = useRef(null);

    const openPicker = () => {
        const input = inputRef.current;
        if (! input) {
            return;
        }

        if (typeof input.showPicker === 'function') {
            try {
                input.showPicker();
            } catch {
                input.focus();
            }
            return;
        }

        input.focus();
    };

    return (
        <div className="relative cursor-pointer" onClick={openPicker}>
            <input
                ref={inputRef}
                id={name}
                name={name}
                type="date"
                value={value}
                onChange={onChange}
                onClick={(event) => {
                    event.stopPropagation();
                    openPicker();
                }}
                className={`${inputClass} cursor-pointer pr-8 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0`}
            />
            <CalendarDays className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
        </div>
    );
}

export default function AddWorkerDrawer({ open, onClose, projects = [], company = {}, positions = [] }) {
    const fileInputRef = useRef(null);
    const { data, setData, post, processing, errors, reset, progress } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        gender: '',
        employee_id: '',
        position: '',
        trade: '',
        primary_project_id: '',
        status: 'active',
        start_date: '',
        end_date: '',
        notes: '',
        documents: [],
        location: '',
        on_site: false,
    });

    useEffect(() => {
        if (!open) return undefined;

        const onKeyDown = (event) => {
            if (event.key === 'Escape') onClose();
        };

        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);

    const submit = (event) => {
        event.preventDefault();
        post(route('workers.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    if (!open) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-50">
            <aside className="pointer-events-auto absolute inset-y-0 right-0 flex w-full flex-col border-l border-slate-200 bg-white shadow-2xl sm:w-[clamp(310px,32vw,390px)]">
                <header className="flex items-start justify-between border-b border-slate-100 px-4 py-4">
                    <div>
                        <h2 className="text-[15px] font-bold text-slate-900">Add Worker</h2>
                        <p className="mt-1 text-[9px] text-slate-500">
                            Enter worker details to add them to Crew Hub.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </header>

                <form onSubmit={submit} className="flex min-h-0 flex-1 flex-col">
                    <div className="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-3">
                        <section>
                            <h3 className="mb-2 text-[10px] font-semibold text-indigo-700">Personal Information</h3>
                            <div className="grid grid-cols-2 gap-2">
                                <Field label="First Name" required error={errors.first_name}>
                                    <input
                                        autoFocus
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className={inputClass}
                                        placeholder="Enter first name"
                                    />
                                </Field>
                                <Field label="Last Name" required error={errors.last_name}>
                                    <input
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        className={inputClass}
                                        placeholder="Enter last name"
                                    />
                                </Field>
                                <Field label="Email" required error={errors.email} className="col-span-2">
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className={inputClass}
                                        placeholder="Enter email address"
                                    />
                                </Field>
                                <Field label="Phone Number" error={errors.phone} className="col-span-2">
                                    <input
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className={inputClass}
                                        placeholder="+1 (250) 555-0123"
                                    />
                                </Field>
                                <Field label="Gender" error={errors.gender} className="col-span-2">
                                    <SelectField
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                    >
                                        <option value="">Select gender</option>
                                        <option value="female">Female</option>
                                        <option value="male">Male</option>
                                        <option value="non_binary">Non-binary</option>
                                        <option value="prefer_not_to_say">Prefer not to say</option>
                                    </SelectField>
                                </Field>
                            </div>
                        </section>

                        <section>
                            <h3 className="mb-2 text-[10px] font-semibold text-indigo-700">Employment Information</h3>
                            <div className="grid grid-cols-2 gap-2">
                                <Field label="Employee ID" error={errors.employee_id}>
                                    <input
                                        value={data.employee_id}
                                        onChange={(e) => setData('employee_id', e.target.value)}
                                        className={inputClass}
                                        placeholder="Enter employee ID"
                                    />
                                </Field>
                                <Field label="Company" required>
                                    <SelectField value={company.id || ''} onChange={() => {}} disabled>
                                        <option value={company.id || ''}>{company.name || 'Current company'}</option>
                                    </SelectField>
                                </Field>
                                <Field label="Role / Position" required error={errors.position}>
                                    {positions.length > 0 ? (
                                        <SearchableSelect
                                            value={data.position}
                                            onChange={(position) => setData('position', position)}
                                            options={positions}
                                            placeholder="Select role / position"
                                            className={inputClass}
                                            ariaLabel="Role / Position"
                                        />
                                    ) : (
                                        <input
                                            value={data.position}
                                            onChange={(e) => setData('position', e.target.value)}
                                            className={inputClass}
                                            placeholder="Select role / position"
                                        />
                                    )}
                                </Field>
                                <Field label="Trade / Discipline" error={errors.trade}>
                                    <input
                                        value={data.trade}
                                        onChange={(e) => setData('trade', e.target.value)}
                                        className={inputClass}
                                        placeholder="Select trade / discipline"
                                    />
                                </Field>
                                <Field label="Primary Project" error={errors.primary_project_id}>
                                    <SelectField
                                        value={data.primary_project_id}
                                        onChange={(e) => setData('primary_project_id', e.target.value)}
                                    >
                                        <option value="">Select primary project</option>
                                        {projects.map((project) => (
                                            <option key={project.id} value={project.id}>
                                                {project.name}
                                            </option>
                                        ))}
                                    </SelectField>
                                </Field>
                                <Field label="Status" required error={errors.status}>
                                    <SelectField
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on_leave">On Leave</option>
                                        <option value="mobilizing">Mobilizing</option>
                                        <option value="demobilizing">Demobilizing</option>
                                    </SelectField>
                                </Field>
                            </div>
                        </section>

                        <section>
                            <h3 className="mb-2 text-[10px] font-semibold text-indigo-700">Additional Information</h3>
                            <div className="grid grid-cols-2 gap-2">
                                <Field label="Start Date" error={errors.start_date}>
                                    <DateField
                                        name="start_date"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                    />
                                </Field>
                                <Field label="End Date (Optional)" error={errors.end_date}>
                                    <DateField
                                        name="end_date"
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                    />
                                </Field>
                                <Field label="Notes (Optional)" error={errors.notes} className="col-span-2">
                                    <textarea
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className={`${inputClass} h-12 resize-none py-2`}
                                        placeholder="Enter notes about the worker"
                                    />
                                </Field>
                            </div>
                        </section>

                        <section>
                            <h3 className="mb-2 text-[10px] font-semibold text-indigo-700">Document Uploads</h3>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                multiple
                                className="hidden"
                                onChange={(e) => setData('documents', Array.from(e.target.files || []))}
                            />
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className="flex min-h-[64px] w-full flex-col items-center justify-center rounded border border-dashed border-indigo-400 bg-indigo-50/30 px-3 text-center"
                            >
                                <span className="flex items-center gap-1.5 text-[9px] font-medium text-slate-700">
                                    <Upload className="h-3.5 w-3.5 text-indigo-600" />
                                    Drag & drop files here or click to browse
                                </span>
                                <span className="mt-1 text-[8px] text-slate-400">Supported: PDF, JPG, PNG (Max 10MB)</span>
                                {data.documents.length > 0 && (
                                    <span className="mt-1 text-[8px] font-medium text-indigo-600">
                                        {data.documents.length} file(s) selected
                                    </span>
                                )}
                            </button>
                            {errors.documents && <p className="mt-1 text-[8px] text-rose-600">{errors.documents}</p>}
                            {progress && (
                                <div className="mt-2 h-1 overflow-hidden rounded bg-slate-100">
                                    <div className="h-full bg-indigo-600" style={{ width: `${progress.percentage}%` }} />
                                </div>
                            )}
                        </section>
                    </div>

                    <footer className="grid grid-cols-2 gap-2 border-t border-slate-100 bg-white px-4 py-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="h-8 rounded border border-slate-200 text-[10px] font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-8 items-center justify-center gap-1.5 rounded bg-indigo-600 text-[10px] font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60"
                        >
                            <Save className="h-3.5 w-3.5" />
                            {processing ? 'Saving...' : 'Save Worker'}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    );
}
