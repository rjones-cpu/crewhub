import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LogOut, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import { statusLabel } from '@/utils/formatters';

export default function UserMenu() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        const onClick = (event) => {
            if (ref.current && !ref.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onClick);

        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="inline-flex items-center gap-1 rounded-lg p-1 transition hover:bg-slate-100"
                aria-label="Account menu"
            >
                <Avatar name={user?.name} src={user?.avatar} size="sm" />
                <ChevronDown className="h-4 w-4 text-slate-400" />
            </button>

            {open && (
                <div className="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    <div className="border-b border-slate-100 px-3 py-2">
                        <p className="truncate text-sm font-medium text-slate-900">{user?.name}</p>
                        <p className="truncate text-xs text-slate-500">{user?.email}</p>
                        {user?.role && (
                            <p className="truncate text-xs text-slate-400">{statusLabel(user.role)}</p>
                        )}
                    </div>
                    <Link
                        href={route('profile.edit')}
                        className="flex items-center gap-2 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                        onClick={() => setOpen(false)}
                    >
                        <User className="h-4 w-4" />
                        Profile
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="flex w-full items-center gap-2 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                        onClick={() => setOpen(false)}
                    >
                        <LogOut className="h-4 w-4" />
                        Logout
                    </Link>
                </div>
            )}
        </div>
    );
}
