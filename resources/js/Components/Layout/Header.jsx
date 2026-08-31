import { Link, router, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    CalendarDays,
    ChevronDown,
    Clock,
    Menu,
    RefreshCw,
    SquarePlus,
    UserRoundPlus,
} from 'lucide-react';
import { formatDate } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import IconButton from '@/Components/Shared/IconButton';
import NotificationMenu from './NotificationMenu';
import UserMenu from './UserMenu';

const TAB_ICONS = {
    CalendarCheck,
    SquarePlus,
    UserRoundPlus,
};

export default function Header({
    title,
    titleIcon: TitleIcon,
    subtitle,
    subtitleMeta,
    dateLabel,
    tabs = [],
    activeTab,
    tabsVariant = 'underline',
    showMeta = true,
    headerAction,
    onMenuClick,
}) {
    const boxedTabs = tabsVariant === 'boxed';
    const { currentProject } = usePage().props;
    const period = dateLabel || formatDate(new Date());
    const loadedAt = new Date().toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div
                className={cn(
                    'flex flex-col gap-3 px-4 pt-3 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8',
                    tabs.length > 0 ? 'pb-2' : 'pb-3',
                )}
            >
                <div className="flex min-w-0 items-start gap-3">
                    <IconButton
                        label="Open menu"
                        className="mt-0.5 lg:hidden"
                        onClick={onMenuClick}
                    >
                        <Menu className="h-5 w-5" />
                    </IconButton>
                    {TitleIcon && (
                        <span className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand">
                            <TitleIcon className="h-5 w-5" />
                        </span>
                    )}
                    <div>
                        {title && <h1 className="page-title">{title}</h1>}
                        {subtitle && <p className="page-subtitle mt-0.5">{subtitle}</p>}
                        {!subtitle && currentProject && (
                            <p className="page-subtitle mt-0.5">
                                {currentProject.name}
                                {currentProject.location ? ` · ${currentProject.location}` : ''}
                            </p>
                        )}
                        {subtitleMeta && (
                            <p className="mt-1 flex items-center gap-1.5 text-[11px] text-slate-400">
                                <Clock className="h-3 w-3 shrink-0" />
                                {subtitleMeta}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {showMeta && (
                        <>
                            <div className="hidden items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-600 sm:flex">
                                <CalendarDays className="h-3.5 w-3.5 text-slate-400" />
                                <span className="whitespace-nowrap">{period}</span>
                                <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                            </div>

                            <span className="hidden items-center gap-1.5 whitespace-nowrap text-xs font-medium text-success lg:inline-flex">
                                <span className="relative flex h-2 w-2">
                                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-60" />
                                    <span className="relative inline-flex h-2 w-2 rounded-full bg-success" />
                                </span>
                                Data as of {loadedAt}
                            </span>
                        </>
                    )}

                    {headerAction}
                    <NotificationMenu />

                    {showMeta && (
                        <button
                            type="button"
                            onClick={() => router.reload()}
                            className="btn-secondary min-h-8 whitespace-nowrap px-2.5 py-1.5 text-xs"
                        >
                            <RefreshCw className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">Refresh</span>
                        </button>
                    )}

                    <UserMenu />
                </div>
            </div>

            {tabs.length > 0 && (
                <nav
                    className={cn(
                        'flex overflow-x-auto px-4 sm:px-6 lg:px-8',
                        boxedTabs ? 'gap-2' : 'gap-6',
                    )}
                >
                    {tabs.map((tab) => {
                        const active = activeTab
                            ? activeTab === tab.route
                            : route().current(tab.route);
                        const TabIcon = TAB_ICONS[tab.icon];

                        return (
                            <Link
                                key={tab.route}
                                href={route(tab.route)}
                                className={cn(
                                    '-mb-px inline-flex items-center gap-2 whitespace-nowrap text-sm transition',
                                    boxedTabs
                                        ? 'rounded-t-lg border border-b-2 px-4 pb-2 pt-2'
                                        : 'border-b-2 px-1 pb-2.5',
                                    active
                                        ? cn(
                                            'border-b-brand font-semibold text-brand',
                                            boxedTabs
                                                ? 'border-slate-200 bg-white'
                                                : 'border-brand',
                                        )
                                        : cn(
                                            'font-medium text-slate-500 hover:text-slate-700',
                                            boxedTabs
                                                ? 'border-slate-200 border-b-transparent bg-white hover:bg-slate-50'
                                                : 'border-transparent hover:border-slate-300',
                                        ),
                                )}
                            >
                                {boxedTabs && TabIcon && (
                                    <TabIcon className="h-4 w-4 shrink-0" strokeWidth={1.8} />
                                )}
                                {tab.name}
                            </Link>
                        );
                    })}
                </nav>
            )}
        </header>
    );
}
