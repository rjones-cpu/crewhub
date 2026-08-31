import { Link, usePage } from '@inertiajs/react';
import { Blocks, Briefcase, UserRoundCog } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function SettingsSubnav() {
    const { auth } = usePage().props;
    const role = auth?.user?.role;

    const items = [
        {
            name: 'Profile',
            route: 'settings.index',
            match: ['settings.index', 'profile.edit'],
            icon: UserRoundCog,
            visible: true,
        },
        {
            name: 'Positions',
            route: 'settings.positions.index',
            match: ['settings.positions.*'],
            icon: Briefcase,
            visible: ['super_admin', 'company_admin', 'workforce_manager'].includes(role),
        },
        {
            name: 'Modules',
            route: 'settings.modules.index',
            match: ['settings.modules.*'],
            icon: Blocks,
            visible: role === 'super_admin',
        },
    ].filter((item) => item.visible);

    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[210px]">
            <div className="border-b border-slate-100 px-3 py-2.5">
                <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Settings
                </p>
            </div>
            <nav className="flex-1 p-2">
                <ul className="space-y-0.5">
                    {items.map((item) => {
                        const Icon = item.icon;
                        const active = item.match.some((pattern) => route().current(pattern));

                        return (
                            <li key={item.route}>
                                <Link
                                    href={route(item.route)}
                                    className={cn(
                                        'flex items-center gap-2 rounded-md px-2.5 py-2 text-[11px] font-medium leading-tight transition',
                                        active
                                            ? 'border-l-2 border-brand bg-brand-soft text-brand'
                                            : 'border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                                    )}
                                >
                                    <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.8} />
                                    <span>{item.name}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </aside>
    );
}
