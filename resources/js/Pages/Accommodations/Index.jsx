import { Head, Link } from '@inertiajs/react';
import {
    BedDouble,
    BellRing,
    Briefcase,
    Building2,
    Bus,
    CalendarDays,
    ChevronRight,
    CircleHelp,
    ExternalLink,
    Headphones,
    Hotel,
    Info,
    MapPin,
    RefreshCw,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import ProjectThumbnail from '@/Components/MajorProjects/ProjectThumbnail';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';

const CONCIERGE_FEATURES = [
    {
        icon: Hotel,
        tone: 'bg-blue-50 text-blue-600',
        title: 'Hotel Booking Management',
        description: 'We find and book the best hotels based on location, policy, and rates.',
    },
    {
        icon: Bus,
        tone: 'bg-emerald-50 text-emerald-600',
        title: 'Transportation Coordination',
        description: 'Flights, ground transport, and transfers—organized for your team.',
    },
    {
        icon: CalendarDays,
        tone: 'bg-violet-50 text-violet-600',
        title: 'Itinerary Management',
        description: 'We manage changes, delays, and cancellations so you do not have to.',
    },
    {
        icon: BellRing,
        tone: 'bg-amber-50 text-amber-600',
        title: '24/7 Travel Support',
        description: 'Your workforce has access to concierge support, anytime, anywhere.',
    },
];

function SectionHeading({ number, icon: Icon, title, description, onHelp }) {
    return (
        <div className="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <div className="flex min-w-0 items-start gap-3">
                <span className="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600">
                    <Icon className="h-4 w-4" strokeWidth={1.8} />
                </span>
                <div className="min-w-0">
                    <h2 className="text-sm font-semibold text-slate-900">
                        {number}. {title}
                    </h2>
                    <p className="mt-0.5 text-[11px] text-slate-500">{description}</p>
                </div>
            </div>
            <button
                type="button"
                onClick={onHelp}
                className="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-blue-200 bg-white px-2.5 py-1.5 text-[10px] font-medium text-blue-600 transition hover:bg-blue-50"
            >
                <CircleHelp className="h-3.5 w-3.5" />
                How it works
            </button>
        </div>
    );
}

function Metric({ icon: Icon, tone, label, value, hint }) {
    return (
        <div className="min-w-0 flex-1 px-3 py-3">
            <div className="flex items-start gap-2">
                <Icon className={cn('mt-0.5 h-4 w-4 shrink-0', tone)} strokeWidth={1.8} />
                <div className="min-w-0">
                    <p className="text-[10px] font-medium text-slate-500">{label}</p>
                    <p className="mt-1 truncate text-lg font-semibold leading-none text-slate-900">
                        {value}
                    </p>
                    <p className="mt-1 text-[9px] leading-3 text-slate-400">{hint}</p>
                </div>
            </div>
        </div>
    );
}

function ConciergeIllustration() {
    return (
        <div className="relative mx-auto h-32 max-w-sm overflow-hidden">
            <div className="absolute inset-x-3 bottom-2 h-8 rounded-[50%] bg-blue-50/80" />
            <div className="absolute bottom-5 left-1/2 h-20 w-24 -translate-x-1/2 rounded-t-lg border-2 border-blue-200 bg-gradient-to-b from-blue-100 to-white shadow-sm">
                <div className="absolute inset-x-0 top-0 bg-blue-500 py-1 text-center text-[8px] font-bold tracking-[0.35em] text-white">
                    HOTEL
                </div>
                <div className="grid grid-cols-3 gap-2 px-3 pt-7">
                    {Array.from({ length: 6 }).map((_, index) => (
                        <span key={index} className="h-2 rounded-sm bg-blue-200" />
                    ))}
                </div>
                <span className="absolute bottom-0 left-1/2 h-5 w-4 -translate-x-1/2 rounded-t bg-blue-300" />
            </div>
            <div className="absolute bottom-5 left-[16%] flex items-end text-blue-500">
                <Bus className="h-12 w-12 fill-blue-50" strokeWidth={1.4} />
            </div>
            <div className="absolute bottom-4 right-[13%] grid h-16 w-16 place-items-center rounded-full border-2 border-blue-200 bg-white shadow-sm">
                <Briefcase className="h-8 w-8 text-blue-600" strokeWidth={1.7} />
            </div>
            <span className="absolute bottom-5 left-[7%] h-14 w-8 rounded-t-full bg-blue-50/80" />
            <span className="absolute bottom-5 right-[5%] h-12 w-7 rounded-t-full bg-blue-50/80" />
        </div>
    );
}

export default function AccommodationsIndex({
    accommodations,
    linkedProject,
    overview = {},
}) {
    const { items, links, meta } = unwrapPaginated(accommodations);
    const [activeSection, setActiveSection] = useState('major');

    const totalRooms = Number(overview.total_rooms || 0);
    const roomsUsed = Number(overview.rooms_used || 0);
    const utilization = totalRooms > 0 ? Math.round((roomsUsed / totalRooms) * 100) : 0;

    const scrollTo = (id, section) => {
        setActiveSection(section);
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <AppLayout
            title="Accommodations"
            subtitle="Manage major project accommodations and travel accommodations for your workforce."
            showMeta={false}
        >
            <Head title="Accommodations" />

            <div className="mx-auto max-w-[1440px] space-y-3">
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => scrollTo('major-project-accommodations', 'major')}
                        className={cn(
                            'inline-flex min-h-9 items-center gap-2 rounded-md border px-4 text-xs font-medium transition',
                            activeSection === 'major'
                                ? 'border-blue-200 bg-white text-blue-700 shadow-sm'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        )}
                    >
                        <Building2 className="h-4 w-4" strokeWidth={1.8} />
                        Major Projects Accommodations
                    </button>
                    <button
                        type="button"
                        onClick={() => scrollTo('accommodations-concierge', 'concierge')}
                        className={cn(
                            'inline-flex min-h-9 items-center gap-2 rounded-md border px-4 text-xs font-medium transition',
                            activeSection === 'concierge'
                                ? 'border-blue-200 bg-white text-blue-700 shadow-sm'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        )}
                    >
                        <BellRing className="h-4 w-4" strokeWidth={1.8} />
                        Accommodations Concierge
                    </button>
                </div>

                <div className="grid items-start gap-3 xl:grid-cols-2">
                    <section
                        id="major-project-accommodations"
                        className="scroll-mt-24 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
                    >
                        <SectionHeading
                            number="1"
                            icon={Building2}
                            title="Major Projects Accommodations"
                            description="Join a Major Project to access and manage project accommodations."
                            onHelp={() => scrollTo('accommodation-help', 'major')}
                        />

                        <div className="space-y-3 p-4">
                            <div>
                                <p className="mb-2 text-[10px] font-semibold text-slate-700">
                                    Linked Major Project
                                </p>
                                {linkedProject ? (
                                    <div className="flex flex-wrap items-center gap-3">
                                        <ProjectThumbnail
                                            project={linkedProject}
                                            className="h-14 w-16 rounded-lg"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-xs font-semibold text-slate-900">
                                                {linkedProject.name}
                                            </p>
                                            <p className="mt-0.5 truncate text-[10px] text-slate-500">
                                                {[linkedProject.code, linkedProject.company_name]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                            <div className="mt-1.5 flex flex-wrap items-center gap-3 text-[9px] text-slate-500">
                                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                                    <ShieldCheck className="h-3 w-3" />
                                                    Active
                                                </span>
                                                {linkedProject.joined_at && (
                                                    <span className="inline-flex items-center gap-1">
                                                        <Users className="h-3 w-3" />
                                                        Joined on {formatDate(linkedProject.joined_at)}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <Link
                                            href={route('major-projects.index')}
                                            className="inline-flex min-h-8 items-center gap-1.5 rounded-md border border-slate-200 px-2.5 text-[10px] font-medium text-blue-700 transition hover:bg-blue-50"
                                        >
                                            <RefreshCw className="h-3 w-3" />
                                            Change Project
                                        </Link>
                                    </div>
                                ) : (
                                    <EmptyState
                                        title="No linked Major Project"
                                        description="Join or select a Major Project to see its accommodation portfolio."
                                        action={
                                            <Link
                                                href={route('major-projects.join')}
                                                className="btn-primary min-h-8 px-3 text-xs"
                                            >
                                                Join a Project
                                            </Link>
                                        }
                                    />
                                )}
                            </div>

                            <div>
                                <p className="mb-2 text-[10px] font-semibold text-slate-700">
                                    Project Accommodations Overview
                                </p>
                                <div className="flex divide-x divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                                    <Metric
                                        icon={BedDouble}
                                        tone="text-violet-600"
                                        label="Accommodations"
                                        value={overview.primary_lodge || 'Not assigned'}
                                        hint={`${formatNumber(overview.facility_count || 0)} facilities`}
                                    />
                                    <Metric
                                        icon={Users}
                                        tone="text-blue-600"
                                        label="Total Rooms Assigned"
                                        value={formatNumber(totalRooms)}
                                        hint="rooms assigned to your company"
                                    />
                                    <Metric
                                        icon={CalendarDays}
                                        tone="text-emerald-600"
                                        label="Upcoming Arrivals"
                                        value={formatNumber(overview.upcoming_arrivals || 0)}
                                        hint="Next 7 days"
                                    />
                                    <Metric
                                        icon={Hotel}
                                        tone="text-amber-600"
                                        label="Rooms Used"
                                        value={`${formatNumber(roomsUsed)} of ${formatNumber(totalRooms)}`}
                                        hint={`${utilization}% of rooms in use`}
                                    />
                                </div>
                            </div>

                            <div>
                                <div className="mb-2 flex items-center justify-between">
                                    <p className="text-[10px] font-semibold text-slate-700">
                                        Major Project Accommodations
                                    </p>
                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-medium text-slate-600">
                                        {formatNumber(meta?.total ?? items.length)}
                                    </span>
                                </div>

                                {items.length === 0 ? (
                                    <div className="rounded-lg border border-slate-200 p-4">
                                        <EmptyState
                                            title="No accommodations"
                                            description="Lodging facilities linked to this project will appear here."
                                        />
                                    </div>
                                ) : (
                                    <div className="overflow-hidden rounded-lg border border-slate-200">
                                        <ul className="divide-y divide-slate-100">
                                            {items.map((item) => {
                                                const occupied = Number(
                                                    item.occupied ?? item.assignments_count ?? 0,
                                                );
                                                const capacity = Number(item.capacity || 0);
                                                const percent =
                                                    capacity > 0
                                                        ? Math.min(
                                                            100,
                                                            Math.round((occupied / capacity) * 100),
                                                        )
                                                        : 0;

                                                return (
                                                    <li
                                                        key={item.id}
                                                        className="flex items-center gap-3 px-3 py-2.5 transition hover:bg-slate-50"
                                                    >
                                                        <span className="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600">
                                                            <BedDouble className="h-4 w-4" />
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate text-[11px] font-semibold text-slate-800">
                                                                {item.name}
                                                            </p>
                                                            <p className="mt-0.5 inline-flex items-center gap-1 truncate text-[9px] text-slate-500">
                                                                <MapPin className="h-2.5 w-2.5 shrink-0" />
                                                                {item.location || 'Location unavailable'}
                                                            </p>
                                                        </div>
                                                        <div className="hidden w-24 sm:block">
                                                            <div className="mb-1 flex justify-between text-[9px] text-slate-500">
                                                                <span>{occupied} used</span>
                                                                <span>{capacity}</span>
                                                            </div>
                                                            <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                                <div
                                                                    className="h-full rounded-full bg-blue-500"
                                                                    style={{ width: `${percent}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                        <Link
                                                            href={route('accommodations.show', item.id)}
                                                            className="inline-flex min-h-7 shrink-0 items-center gap-1 rounded-md border border-slate-200 px-2 text-[9px] font-medium text-blue-700 transition hover:bg-blue-50"
                                                        >
                                                            View Details
                                                            <ChevronRight className="h-3 w-3" />
                                                        </Link>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                )}
                                {meta?.last_page > 1 && (
                                    <Pagination links={links} meta={meta} className="mt-3" />
                                )}
                            </div>

                            {linkedProject && (
                                <div className="flex items-start gap-2 rounded-md border-l-2 border-blue-500 bg-blue-50 px-3 py-2 text-[9px] text-blue-700">
                                    <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                    <p>
                                        You are currently linked to this Major Project. All
                                        accommodation data shown above is for this project.
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>

                    <section
                        id="accommodations-concierge"
                        className="scroll-mt-24 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
                    >
                        <SectionHeading
                            number="2"
                            icon={BellRing}
                            title="Accommodations Concierge"
                            description="Let LodgeX manage your workforce accommodations when they travel."
                            onHelp={() => scrollTo('accommodation-help', 'concierge')}
                        />

                        <div className="px-4 pb-4 pt-2">
                            <ConciergeIllustration />
                            <div className="mx-auto max-w-md text-center">
                                <h3 className="text-sm font-semibold text-slate-900">
                                    We Handle the Details. You Focus on the Work.
                                </h3>
                                <p className="mt-1 text-[10px] leading-4 text-slate-500">
                                    Our concierge service manages hotel bookings, transportation,
                                    and travel logistics so your workforce has a smooth travel
                                    experience—every time.
                                </p>
                            </div>

                            <ul className="mt-4 divide-y divide-slate-100 border-y border-slate-100">
                                {CONCIERGE_FEATURES.map((feature) => {
                                    const Icon = feature.icon;

                                    return (
                                        <li key={feature.title} className="flex items-center gap-3 py-3">
                                            <span
                                                className={cn(
                                                    'grid h-8 w-8 shrink-0 place-items-center rounded-lg',
                                                    feature.tone,
                                                )}
                                            >
                                                <Icon className="h-4 w-4" strokeWidth={1.8} />
                                            </span>
                                            <div>
                                                <p className="text-[11px] font-semibold text-slate-800">
                                                    {feature.title}
                                                </p>
                                                <p className="mt-0.5 text-[9px] leading-3 text-slate-500">
                                                    {feature.description}
                                                </p>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>

                            <div className="mt-4 text-center">
                                <Link
                                    href={route('communications.index', {
                                        topic: 'accommodation-concierge',
                                    })}
                                    className="btn-primary mx-auto min-h-9 w-full max-w-[270px] justify-center px-4 text-xs"
                                >
                                    <Briefcase className="h-3.5 w-3.5" />
                                    Request Concierge Service
                                </Link>
                                <p className="mt-2 text-[9px] text-slate-500">
                                    Submit a request and our team will take care of the rest.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <div
                    id="accommodation-help"
                    className="scroll-mt-24 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="flex items-center gap-3">
                        <span className="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-300 text-slate-600">
                            <CircleHelp className="h-4 w-4" />
                        </span>
                        <div>
                            <p className="text-[11px] font-semibold text-slate-800">
                                Need help with accommodations?
                            </p>
                            <p className="text-[9px] text-slate-500">
                                Contact the LodgeX team or visit our Help Center for more information.
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('communications.index', { topic: 'accommodation-support' })}
                            className="inline-flex min-h-8 items-center gap-2 rounded-md border border-slate-200 px-4 text-[10px] font-medium text-blue-700 transition hover:bg-blue-50"
                        >
                            Contact Support
                            <Headphones className="h-3.5 w-3.5" />
                        </Link>
                        <Link
                            href={route('communications.index', { topic: 'help-center' })}
                            className="inline-flex min-h-8 items-center gap-2 rounded-md border border-slate-200 px-4 text-[10px] font-medium text-blue-700 transition hover:bg-blue-50"
                        >
                            Help Center
                            <ExternalLink className="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
