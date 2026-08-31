import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ChevronDown, Info, ShieldCheck, SquareArrowOutUpRight } from 'lucide-react';
import { useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import CertificatePreviewPanel from '@/Components/Workers/Detail/CertificatePreviewPanel';
import ReadinessPanel from '@/Components/Workers/Detail/ReadinessPanel';
import TrainingComplianceOverview from '@/Components/Workers/Detail/TrainingComplianceOverview';
import TrainingTable from '@/Components/Workers/Detail/TrainingTable';
import UploadCertificationCard from '@/Components/Workers/Detail/UploadCertificationCard';
import WorkerProfileHeader from '@/Components/Workers/Detail/WorkerProfileHeader';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/utils/helpers';

const TABS = [
    { key: 'readiness', name: 'Readiness', icon: ShieldCheck },
    { key: 'training', name: 'Training', icon: BookOpen },
];

export default function WorkersShow({ worker, tab = 'training', training = {} }) {
    const [preview, setPreview] = useState(null);

    const records = training.records ?? [];
    const goToTab = (key) => router.get(
        route('workers.show', worker.id),
        { tab: key },
        { preserveScroll: true, replace: true },
    );

    // Keep the panel bound to the freshest copy of the record after an upload.
    const previewRecord = preview
        ? records.find((record) => record.id === preview.id) ?? preview
        : null;

    return (
        <AppLayout
            title={worker.full_name}
            subtitle="Readiness & Training"
            showMeta={false}
            rightPanelOpen={Boolean(previewRecord)}
            headerAction={(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button
                            type="button"
                            className="inline-flex h-8 items-center gap-1.5 rounded-md bg-indigo-600 px-3 text-[10px] font-semibold text-white shadow-sm hover:bg-indigo-700"
                        >
                            Actions
                            <ChevronDown className="h-3.5 w-3.5" />
                        </button>
                    </Dropdown.Trigger>
                    <Dropdown.Content width="48" contentClasses="bg-white py-1">
                        <Link
                            href={route('workers.edit', worker.id)}
                            className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                        >
                            Edit worker
                        </Link>
                        <Link
                            href={route('workers.activity', worker.id)}
                            className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                        >
                            View activity
                        </Link>
                        <Link
                            href={route('workers.index')}
                            className="block px-3 py-1.5 text-left text-[9px] text-slate-700 hover:bg-slate-50"
                        >
                            Back to workers
                        </Link>
                    </Dropdown.Content>
                </Dropdown>
            )}
        >
            <Head title={worker.full_name} />

            <div className="space-y-3">
                <WorkerProfileHeader worker={worker} />

                <nav className="flex gap-5 border-b border-slate-200">
                    {TABS.map(({ key, name, icon: Icon }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => goToTab(key)}
                            className={cn(
                                '-mb-px flex items-center gap-1.5 border-b-2 px-1 pb-2 text-[11px] transition',
                                tab === key
                                    ? 'border-brand font-semibold text-brand'
                                    : 'border-transparent font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700',
                            )}
                        >
                            <Icon className="h-3.5 w-3.5" />
                            {name}
                        </button>
                    ))}
                </nav>

                {tab === 'readiness' ? (
                    <ReadinessPanel readiness={worker.readiness} />
                ) : (
                    <>
                        <div className="grid gap-3 lg:grid-cols-[minmax(0,1.9fr)_minmax(0,1fr)]">
                            <TrainingComplianceOverview summary={training.summary} />
                            <UploadCertificationCard worker={worker} />
                        </div>

                        <TrainingTable
                            worker={worker}
                            records={records}
                            counts={training.counts}
                            statuses={training.statuses}
                            filters={training.filters}
                            page={training.page}
                            perPage={training.per_page}
                            total={training.total}
                            onPreview={setPreview}
                            selectedCertificateId={previewRecord?.certificate?.id}
                        />

                        <section className="flex items-start gap-2 rounded-lg border border-brand/20 bg-brand-soft/40 p-3">
                            <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand" />
                            <div>
                                <h4 className="text-[10px] font-semibold text-slate-900">
                                    About Training Compliance
                                </h4>
                                <p className="mt-0.5 text-[9px] text-slate-600">
                                    Training compliance is based on required trainings assigned to this worker&apos;s
                                    role, project, and company requirements.
                                </p>
                                <Link
                                    href={route('readiness.index')}
                                    className="mt-1 inline-flex items-center gap-1 text-[9px] font-semibold text-brand hover:underline"
                                >
                                    View all requirements
                                    <SquareArrowOutUpRight className="h-2.5 w-2.5" />
                                </Link>
                            </div>
                        </section>
                    </>
                )}
            </div>

            <CertificatePreviewPanel
                open={Boolean(previewRecord)}
                worker={worker}
                record={previewRecord}
                onClose={() => setPreview(null)}
            />
        </AppLayout>
    );
}
