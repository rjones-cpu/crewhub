import { Head } from '@inertiajs/react';
import SettingsSubnav from '@/Components/Settings/SettingsSubnav';
import AppLayout from '@/Layouts/AppLayout';

export default function SettingsLayout({
    title = 'Settings',
    pageTitle,
    subtitle,
    headerAction,
    children,
}) {
    return (
        <AppLayout
            title={title}
            subtitle={subtitle}
            showMeta={false}
            headerAction={headerAction}
        >
            <Head title={pageTitle || title} />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <SettingsSubnav />
                <div className="min-w-0 flex-1">{children}</div>
            </div>
        </AppLayout>
    );
}
