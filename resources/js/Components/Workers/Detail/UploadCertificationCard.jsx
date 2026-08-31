import { router } from '@inertiajs/react';
import { UploadCloud } from 'lucide-react';
import { useRef, useState } from 'react';
import { cn } from '@/utils/helpers';

const ACCEPTED = '.pdf,.jpg,.jpeg,.png';

export default function UploadCertificationCard({ worker, trainingRecordId = null }) {
    const inputRef = useRef(null);
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);

    const upload = (file) => {
        if (!file) {
            return;
        }

        setError(null);
        setUploading(true);

        router.post(
            route('workers.certificates.store', worker.id),
            { file, training_record_id: trainingRecordId },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => setError(errors.file || 'Upload failed.'),
                onFinish: () => setUploading(false),
            },
        );
    };

    const onDrop = (event) => {
        event.preventDefault();
        setDragging(false);
        upload(event.dataTransfer.files?.[0]);
    };

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
            <h3 className="text-[11px] font-semibold text-slate-900">Upload New Certification</h3>

            <div
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                className={cn(
                    'mt-3 flex flex-col items-center justify-center rounded-lg border border-dashed px-4 py-5 text-center transition',
                    dragging ? 'border-brand bg-brand-soft/40' : 'border-slate-300 bg-slate-50/60',
                )}
            >
                <UploadCloud className={cn('h-6 w-6', dragging ? 'text-brand' : 'text-slate-400')} />
                <p className="mt-2 text-[10px] text-slate-600">Drag and drop file here</p>
                <p className="text-[9px] text-slate-400">or</p>

                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    disabled={uploading}
                    className="mt-1.5 inline-flex h-7 items-center rounded-md border border-brand bg-white px-3 text-[10px] font-semibold text-brand hover:bg-brand-soft disabled:opacity-60"
                >
                    {uploading ? 'Uploading…' : 'Choose File'}
                </button>

                <input
                    ref={inputRef}
                    type="file"
                    accept={ACCEPTED}
                    className="hidden"
                    onChange={(event) => {
                        upload(event.target.files?.[0]);
                        event.target.value = '';
                    }}
                />
            </div>

            <p className="mt-2 text-center text-[9px] text-slate-400">
                Accepted formats: PDF, JPG, PNG (Max 10MB)
            </p>

            {error && <p className="mt-1 text-center text-[9px] font-medium text-danger">{error}</p>}
        </section>
    );
}
