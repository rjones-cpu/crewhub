import { Link, usePage } from '@inertiajs/react';
import { Building2, ChevronUp, LogOut, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Avatar from '@/Components/Shared/Avatar';
import { statusLabel } from '@/utils/formatters';

export default function SidebarUserMenu() {
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
            {open && (
                <div className="absolute bottom-full left-0 z-40 mb-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
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

            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-center gap-2 rounded px-1 py-1 text-left transition hover:bg-white/5"
            >
                <Avatar
                    name={user?.name}
                    src={user?.avatar}
                    size="sm"
                    className="h-8 w-8 text-[9px] ring-sidebar"
                />
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-[10px] font-semibold leading-tight text-white">
                        {user?.name}
                    </span>
                    {user?.role && (
                        <span className="block truncate text-[8px] leading-tight text-slate-300">
                            {statusLabel(user.role)}
                        </span>
                    )}
                    {user?.company?.name && (
                        <span className="mt-0.5 flex items-center gap-1 text-[8px] leading-tight text-slate-400">
                            <Building2 className="h-2.5 w-2.5 shrink-0" />
                            <span className="truncate">{user.company.name}</span>
                        </span>
                    )}
                </span>
                <ChevronUp className="h-3.5 w-3.5 shrink-0 text-slate-300" />
            </button>
        </div>
    );
}
