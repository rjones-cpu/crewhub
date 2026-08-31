import { Head, router } from '@inertiajs/react';
import { Building2, Check, X } from 'lucide-react';
import { useState } from 'react';
import Badge from '@/Components/Shared/Badge';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { unwrapPaginated } from '@/utils/helpers';

function statusValue(status) {
    return status?.value || status;
}

function formatTimestamp(value) {
    if (!value) return '';

    return new Intl.DateTimeFormat('en-CA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function NotificationsIndex({ notifications }) {
    const { items, links, meta } = unwrapPaginated(notifications);
    const [processingRequest, setProcessingRequest] = useState(null);

    const markAllRead = () => {
        router.post(route('notifications.mark-all-read'));
    };

    const markRead = (id) => {
        router.patch(route('notifications.update', id), { read: true }, { preserveScroll: true });
    };

    const approveRequest = (notification) => {
        const requestId = notification.data?.request_id;
        if (!requestId) return;

        setProcessingRequest(`approve-${requestId}`);
        router.post(
            route('notifications.activation-requests.approve', requestId),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingRequest(null),
            },
        );
    };

    const rejectRequest = (notification) => {
        const requestId = notification.data?.request_id;
        if (!requestId) return;

        const reason = window.prompt('Optional rejection reason:', '');
        if (reason === null) return;

        setProcessingRequest(`reject-${requestId}`);
        router.post(
            route('notifications.activation-requests.reject', requestId),
            { rejection_reason: reason },
            {
                preserveScroll: true,
                onFinish: () => setProcessingRequest(null),
            },
        );
    };

    return (
        <AppLayout title="Notifications" subtitle="Alerts and system updates">
            <Head title="Notifications" />

            <div className="mb-4 flex justify-end">
                <Button variant="secondary" onClick={markAllRead}>
                    Mark all read
                </Button>
            </div>

            <Card title="Inbox">
                {items.length === 0 ? (
                    <EmptyState title="No notifications" description="You're all caught up." />
                ) : (
                    <>
                        <ul className="divide-y divide-slate-100">
                            {items.map((notification) => {
                                const data = notification.data || {};
                                const unread = !notification.read_at;
                                const isActivationRequest =
                                    notification.type === 'module_activation_request';
                                const requestStatus = statusValue(notification.request_status);
                                const pending = isActivationRequest && requestStatus === 'pending';

                                return (
                                    <li
                                        key={notification.id}
                                        className={`flex items-start gap-3 py-4 ${unread ? 'bg-brand-soft/10' : ''}`}
                                    >
                                        <div className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-soft text-brand">
                                            <Building2 className="h-4 w-4" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <button
                                                type="button"
                                                className="block w-full text-left"
                                                onClick={() => unread && markRead(notification.id)}
                                            >
                                                <span className="flex flex-wrap items-center gap-2">
                                                    <span className={`text-sm ${unread ? 'font-semibold text-slate-900' : 'font-medium text-slate-700'}`}>
                                                        {notification.title || 'Notification'}
                                                    </span>
                                                    {unread && <Badge tone="brand">New</Badge>}
                                                    {isActivationRequest && requestStatus && !pending && (
                                                        <Badge status={requestStatus}>{requestStatus}</Badge>
                                                    )}
                                                </span>
                                                <span className="mt-1 block text-sm text-slate-600">
                                                    {notification.message || 'A system update is available.'}
                                                </span>
                                                {isActivationRequest && (
                                                    <span className="mt-2 block text-xs text-slate-500">
                                                        <strong className="font-medium text-slate-700">
                                                            {data.company_name || 'Unknown company'}
                                                        </strong>
                                                        {' requested '}
                                                        {data.module_name || 'module'} access
                                                        {data.requested_by_name
                                                            ? ` · Requested by ${data.requested_by_name}`
                                                            : ''}
                                                    </span>
                                                )}
                                                <span className="mt-1.5 block text-xs text-slate-400">
                                                    {formatTimestamp(notification.created_at)}
                                                </span>
                                            </button>

                                            {pending && (
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <Button
                                                        className="min-h-8 gap-1.5 px-3 text-xs"
                                                        disabled={processingRequest !== null}
                                                        onClick={() => approveRequest(notification)}
                                                    >
                                                        <Check className="h-3.5 w-3.5" />
                                                        Approve access
                                                    </Button>
                                                    <Button
                                                        variant="secondary"
                                                        className="min-h-8 gap-1.5 border-danger/30 px-3 text-xs text-danger"
                                                        disabled={processingRequest !== null}
                                                        onClick={() => rejectRequest(notification)}
                                                    >
                                                        <X className="h-3.5 w-3.5" />
                                                        Reject
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                        <Pagination links={links} meta={meta} className="mt-4" />
                    </>
                )}
            </Card>
        </AppLayout>
    );
}
