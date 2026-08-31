import { usePage } from '@inertiajs/react';
import { CheckCircle2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import Header from '@/Components/Layout/Header';
import MobileSidebar from '@/Components/Layout/MobileSidebar';
import Sidebar from '@/Components/Layout/Sidebar';
import { cn } from '@/utils/helpers';

export default function AppLayout({
    children,
    title,
    titleIcon,
    subtitle,
    subtitleMeta,
    dateLabel,
    tabs,
    activeTab,
    tabsVariant,
    showMeta,
    headerAction,
    rightPanelOpen = false,
    fitViewport = false,
}) {
    const { flash } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [toast, setToast] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setToast({ type: 'success', message: flash.success });
        } else if (flash?.error) {
            setToast({ type: 'error', message: flash.error });
        }
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        if (!toast) {
            return undefined;
        }

        const timer = setTimeout(() => setToast(null), 4000);

        return () => clearTimeout(timer);
    }, [toast]);

    return (
        <div className={cn('bg-slate-50', fitViewport ? 'h-screen overflow-hidden' : 'min-h-screen')}>
            <div className={cn('flex', fitViewport ? 'h-full' : 'min-h-screen')}>
                <div className="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-48 lg:flex-col">
                    <Sidebar />
                </div>

                <MobileSidebar open={mobileOpen} onClose={() => setMobileOpen(false)} />

                <div
                    className={cn(
                        'flex min-w-0 flex-1 flex-col transition-[padding] duration-300 ease-out lg:pl-48',
                        rightPanelOpen && 'sm:pr-[clamp(310px,32vw,390px)]',
                    )}
                >
                    <Header
                        title={title}
                        titleIcon={titleIcon}
                        subtitle={subtitle}
                        subtitleMeta={subtitleMeta}
                        dateLabel={dateLabel}
                        tabs={tabs}
                        activeTab={activeTab}
                        tabsVariant={tabsVariant}
                        showMeta={showMeta}
                        headerAction={headerAction}
                        onMenuClick={() => setMobileOpen(true)}
                    />
                    <main
                        className={cn(
                            'flex-1 px-4 sm:px-6 lg:px-8',
                            fitViewport ? 'min-h-0 overflow-hidden py-3' : 'py-5',
                        )}
                    >
                        {children}
                    </main>
                </div>
            </div>

            {toast && (
                <div className="fixed bottom-4 right-4 z-50 max-w-sm">
                    <div
                        className={`flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg ${
                            toast.type === 'error'
                                ? 'border-danger/20 bg-white text-danger'
                                : 'border-success/20 bg-white text-success'
                        }`}
                    >
                        <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0" />
                        <p className="flex-1 text-sm font-medium text-slate-800">{toast.message}</p>
                        <button
                            type="button"
                            onClick={() => setToast(null)}
                            className="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Dismiss"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
