import { router } from '@inertiajs/react';
import { Download, FileText, RefreshCw, X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import Badge from '@/Components/Shared/Badge';
import { formatDate } from '@/utils/formatters';

function formatSize(bytes) {
    if (!bytes) {
        return null;
    }

    const mb = bytes / (1024 * 1024);

    return mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.round(bytes / 1024)} KB`;
}

export default function CertificatePreviewPanel({ open, worker, record, onClose }) {
    const inputRef = useRef(null);
    const certificate = record?.certificate;

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                onClose?.();
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [open, onClose]);

    if (!open || !record) {
        return null;
    }

    const replace = (file) => {
        if (!file) {
            return;
        }

        router.post(
            route('workers.certificates.store', worker.id),
            { file, training_record_id: record.id },
            { forceFormData: true, preserveScroll: true },
        );
    };

    const isImage = certificate?.file_name && /\.(jpe?g|png)$/i.test(certificate.file_name);

    const details = [
        ['Issued By', certificate?.issuer],
        ['Certificate #', certificate?.certificate_number],
        ['Issued Date', certificate?.issued_at ? formatDate(certificate.issued_at) : null],
        ['Expiry Date', certificate?.expires_at ? formatDate(certificate.expires_at) : null],
        ['File Name', certificate?.file_name],
        ['File Size', formatSize(certificate?.file_size)],
        ['Uploaded By', certificate?.uploaded_by],
        ['Uploaded On', certificate?.uploaded_at ? formatDate(certificate.uploaded_at) : null],
    ];

    return (
        <aside className="fixed inset-y-0 right-0 z-40 flex w-[clamp(310px,32vw,390px)] flex-col border-l border-slate-200 bg-white shadow-xl">
            <header className="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2.5">
                <h3 className="text-[11px] font-semibold text-slate-900">Certificate Preview</h3>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close certificate preview"
                >
                    <X className="h-3.5 w-3.5" />
                </button>
            </header>

            <div className="flex-1 space-y-3 overflow-y-auto p-3">
                <div className="flex items-center gap-2">
                    <p className="truncate text-[10px] font-semibold text-slate-800">{record.course_name}</p>
                    <Badge status={record.status} className="px-1.5 py-0.5 text-[8px]" />
                </div>

                <div className="grid min-h-40 place-items-center overflow-hidden rounded-lg border-2 border-danger/30 bg-white p-2">
                    {isImage && certificate?.file_url ? (
                        <img
                            src={certificate.file_url}
                            alt={`${record.course_name} certificate`}
                            className="max-h-56 w-full object-contain"
                        />
                    ) : (
                        <div className="flex flex-col items-center gap-1.5 py-6 text-center">
                            <FileText className="h-8 w-8 text-danger" />
                            <p className="text-[10px] font-semibold text-slate-700">
                                {certificate?.file_name || 'No certificate on file'}
                            </p>
                            <p className="text-[9px] text-slate-400">
                                {certificate ? 'Preview not available — download to view' : 'Upload one to see it here'}
                            </p>
                        </div>
                    )}
                </div>

                {certificate?.file_url && (
                    <a
                        href={certificate.file_url}
                        download
                        className="flex h-8 items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white text-[10px] font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <Download className="h-3.5 w-3.5" />
                        Download Certificate
                    </a>
                )}

                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    className="flex h-8 w-full items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white text-[10px] font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <RefreshCw className="h-3.5 w-3.5" />
                    {certificate ? 'Replace Certificate' : 'Upload Certificate'}
                </button>

                <input
                    ref={inputRef}
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    className="hidden"
                    onChange={(event) => {
                        replace(event.target.files?.[0]);
                        event.target.value = '';
                    }}
                />

                <div>
                    <h4 className="text-[10px] font-semibold text-slate-900">Certificate Details</h4>
                    <dl className="mt-1.5 space-y-1.5">
                        {details.map(([label, value]) => (
                            <div key={label} className="flex items-start justify-between gap-2 text-[9px]">
                                <dt className="text-slate-500">{label}</dt>
                                <dd className="max-w-[60%] truncate text-right font-medium text-slate-800">
                                    {value || '—'}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </aside>
    );
}
