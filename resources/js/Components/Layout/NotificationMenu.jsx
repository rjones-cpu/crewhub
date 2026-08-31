import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

export default function NotificationMenu() {
    const { notificationsCount = 0 } = usePage().props;

    return (
        <Link
            href={route('notifications.index')}
            className="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
            aria-label="Notifications"
        >
            <Bell className="h-5 w-5" />
            {notificationsCount > 0 && (
                <span className="absolute right-1.5 top-1.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold leading-4 text-white">
                    {notificationsCount > 99 ? '99+' : notificationsCount}
                </span>
            )}
        </Link>
    );
}
