import { Link } from '@inertiajs/react';
import {
    CircleHelp,
    Headset,
    List,
    MapPin,
    ShieldCheck,
    TriangleAlert,
    Truck,
} from 'lucide-react';
import { JOURNEY_NAV } from '@/utils/constants';
import { cn } from '@/utils/helpers';

const ICONS = {
    CircleHelp,
    List,
    MapPin,
    ShieldCheck,
    TriangleAlert,
    Truck,
};

export default function JourneySubnav() {
    return (
        <aside className="card flex w-full shrink-0 flex-col lg:sticky lg:top-24 lg:w-[210px]">
            <nav className="flex-1 p-2">
                <ul className="space-y-0.5">
                    {JOURNEY_NAV.map((item) => {
                        const Icon = ICONS[item.icon];
                        const active = route().current(item.route);

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
                                    {Icon && <Icon className="h-3.5 w-3.5 shrink-0" strokeWidth={1.8} />}
                                    <span>{item.name}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
            <div className="m-2 rounded-lg bg-brand-soft px-3 py-3">
                <p className="text-[11px] font-semibold text-brand">Need Help?</p>
                <Link
                    href={route('communications.index')}
                    className="mt-1 inline-flex items-center gap-1.5 text-[11px] font-medium text-brand hover:underline"
                >
                    <Headset className="h-3.5 w-3.5" />
                    Contact Support
                </Link>
            </div>
        </aside>
    );
}
