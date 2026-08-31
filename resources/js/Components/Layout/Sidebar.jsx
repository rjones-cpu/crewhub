import {
    AlertCircle,
    BedDouble,
    Briefcase,
    Calendar,
    Clock,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Mail,
    MessageSquare,
    Network,
    Plane,
    PlaneTakeoff,
    Settings,
    ShieldCheck,
    SlidersHorizontal,
    Truck,
    UserPlus,
    Users,
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import { NAV_ITEMS, QUICK_ACTIONS } from '@/utils/constants';
import { cn } from '@/utils/helpers';
import ProjectSelector from './ProjectSelector';
import SidebarUserMenu from './SidebarUserMenu';

const ICONS = {
    AlertCircle,
    BedDouble,
    Briefcase,
    Calendar,
    Clock,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Mail,
    MessageSquare,
    Network,
    Plane,
    PlaneTakeoff,
    Settings,
    ShieldCheck,
    Truck,
    UserPlus,
    Users,
};

function isActive(routeName) {
    if (routeName === 'dashboard') {
        return route().current('dashboard');
    }

    const base = routeName.replace(/\.index$/, '');

    return route().current(routeName) || route().current(`${base}.*`);
}

export default function Sidebar({ className = '' }) {
    return (
        <aside
            className={cn(
                'flex h-full w-48 flex-col bg-sidebar text-white',
                className,
            )}
        >
            <div className="flex h-11 items-center gap-1.5 px-3">
                <span className="relative h-5 w-5 shrink-0" aria-hidden="true">
                    <span className="absolute left-0.5 top-1 h-1 w-3.5 -rotate-45 rounded-full bg-cyan-400" />
                    <span className="absolute left-1.5 top-2 h-1 w-3.5 -rotate-45 rounded-full bg-sky-500" />
                    <span className="absolute left-2.5 top-3 h-1 w-3 -rotate-45 rounded-full bg-blue-600" />
                </span>
                <p className="whitespace-nowrap text-[15px] font-bold tracking-tight text-white">
                    Lodge<span className="text-orange-500">X</span>
                </p>
            </div>

            <div className="px-2 pb-2">
                <ProjectSelector />
            </div>

            <nav className="no-scrollbar flex-1 overflow-y-auto px-2 pb-2">
                <ul className="space-y-0.5">
                    {NAV_ITEMS.map((item) => {
                        const Icon = ICONS[item.icon];
                        const active = isActive(item.route);

                        return (
                            <li key={item.route}>
                                <Link
                                    href={route(item.route)}
                                    className={cn(
                                        'flex min-h-7 items-center gap-2.5 rounded-md px-2.5 text-[11px] font-medium transition',
                                        active
                                            ? 'bg-[#1D6FF2] text-white shadow-sm'
                                            : 'text-slate-300 hover:bg-white/5 hover:text-white',
                                    )}
                                >
                                    {Icon && <Icon className="h-4 w-4 shrink-0" strokeWidth={1.8} />}
                                    <span className="truncate whitespace-nowrap">{item.name}</span>
                                </Link>

                                {active && item.children && (
                                    <ul className="space-y-0.5 py-1 pl-4">
                                        {item.children.map((child) => {
                                            const childActive = route().current(child.route);

                                            return (
                                                <li key={child.route}>
                                                    <Link
                                                        href={route(child.route)}
                                                        className={cn(
                                                            'flex min-h-5 items-center gap-2 rounded px-1 text-[10px] transition',
                                                            childActive
                                                                ? 'font-medium text-white'
                                                                : 'text-slate-300 hover:text-white',
                                                        )}
                                                    >
                                                        <span
                                                            className={cn(
                                                                'h-1 w-1 shrink-0 rounded-full',
                                                                childActive
                                                                    ? 'bg-white'
                                                                    : 'bg-slate-400',
                                                            )}
                                                        />
                                                        <span className="truncate whitespace-nowrap">
                                                            {child.name}
                                                        </span>
                                                    </Link>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                )}
                            </li>
                        );
                    })}
                </ul>

                <p className="px-2.5 pb-1 pt-4 text-[9px] font-semibold uppercase tracking-wider text-slate-400">
                    Quick Actions
                </p>
                <ul className="space-y-0.5">
                    {QUICK_ACTIONS.map((action) => {
                        const Icon = ICONS[action.icon];

                        return (
                            <li key={action.name}>
                                <Link
                                    href={route(action.route)}
                                    className="flex min-h-7 items-center gap-2.5 rounded-md px-2.5 text-[11px] font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                                >
                                    {Icon && <Icon className="h-4 w-4 shrink-0" strokeWidth={1.8} />}
                                    <span className="truncate whitespace-nowrap">{action.name}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>

                <Link
                    href={route('settings.index')}
                    className="mt-4 flex min-h-7 items-center gap-2.5 rounded-md px-2.5 text-[11px] font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                >
                    <SlidersHorizontal className="h-4 w-4 shrink-0" strokeWidth={1.8} />
                    <span className="truncate whitespace-nowrap">Customize Dashboard</span>
                </Link>
            </nav>

            <div className="border-t border-white/10 p-2">
                <SidebarUserMenu />
            </div>
        </aside>
    );
}
