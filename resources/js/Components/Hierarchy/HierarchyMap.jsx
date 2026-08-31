import { Building2, Info, Plus, Users } from 'lucide-react';
import Avatar from '@/Components/Shared/Avatar';
import Badge from '@/Components/Shared/Badge';
import Card from '@/Components/Shared/Card';
import { formatNumber, statusLabel } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

function Stem() {
    return <div className="mx-auto h-5 w-px bg-slate-200" aria-hidden="true" />;
}

function LevelLabel({ children }) {
    return (
        <p className="py-1 text-center text-[11px] font-medium text-slate-500">{children}</p>
    );
}

/** Org-chart rail: a horizontal line across the row with a drop into each node. */
function Branch({ children }) {
    const nodes = Array.isArray(children) ? children.filter(Boolean) : [children];
    const last = nodes.length - 1;

    return (
        <div className="flex justify-center">
            {nodes.map((node, index) => (
                <div key={index} className="relative w-full max-w-[210px] px-1.5 pt-5">
                    {nodes.length > 1 && (
                        <span
                            aria-hidden="true"
                            className={cn(
                                'absolute top-0 h-px bg-slate-200',
                                index === 0 && 'left-1/2 right-0',
                                index === last && 'left-0 right-1/2',
                                index !== 0 && index !== last && 'left-0 right-0',
                            )}
                        />
                    )}
                    <span
                        aria-hidden="true"
                        className="absolute left-1/2 top-0 h-5 w-px bg-slate-200"
                    />
                    {node}
                </div>
            ))}
        </div>
    );
}

export default function HierarchyMap({
    project,
    company,
    contact,
    managers = [],
    workforceCount = 0,
    onAddManager,
    onOpenWorkforce,
}) {
    return (
        <Card
            className="h-full"
            title={
                <span className="flex flex-wrap items-baseline gap-2">
                    Hierarchy Map
                    <span className="text-xs font-normal text-slate-500">
                        ({project?.name || 'Selected Project'})
                    </span>
                </span>
            }
        >
            <div className="mx-auto max-w-2xl">
                <div className="mx-auto flex max-w-[280px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-card">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                        <Building2 className="h-5 w-5" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-[11px] uppercase tracking-wide text-slate-500">
                            Major Project
                        </p>
                        <p className="truncate text-sm font-semibold text-brand">
                            {project?.name || 'Not connected'}
                        </p>
                        <p className="truncate text-[11px] text-slate-500">{company?.name}</p>
                    </div>
                </div>

                <Stem />
                <LevelLabel>This Crew Hub reports to (manager level)</LevelLabel>

                <Branch>
                    {[
                        ...managers.map((manager) => (
                            <div
                                key={manager.id}
                                className="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-card"
                            >
                                <Avatar name={manager.name} size="sm" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-slate-900">
                                        {manager.name}
                                    </p>
                                    <p className="truncate text-[11px] text-slate-500">
                                        {manager.title}
                                    </p>
                                    <p className="truncate text-[11px] text-slate-400">
                                        {project?.name}
                                    </p>
                                    <div className="mt-1.5 flex justify-end">
                                        <Badge
                                            tone={
                                                manager.relationship === 'primary'
                                                    ? 'success'
                                                    : 'brand'
                                            }
                                        >
                                            {manager.relationship_label}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        )),
                        <button
                            key="add-manager"
                            type="button"
                            onClick={onAddManager}
                            className="flex h-full w-full flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 px-3 py-5 text-sm font-medium text-slate-500 transition hover:border-brand hover:bg-brand-soft hover:text-brand"
                        >
                            <Plus className="h-5 w-5" />
                            Add Manager
                        </button>,
                    ]}
                </Branch>

                <Stem />
                <LevelLabel>Crew Hub / company level</LevelLabel>
                <Stem />

                <div className="mx-auto max-w-[280px] rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-card">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-journey-soft text-journey">
                            <Building2 className="h-5 w-5" />
                        </div>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-900">
                                {company?.name}
                            </p>
                            <p className="truncate text-[11px] text-slate-500">
                                {company?.industry || 'Contractor'}
                            </p>
                        </div>
                    </div>
                    {contact?.name && (
                        <div className="mt-2.5 flex items-center gap-2.5 border-t border-slate-100 pt-2.5">
                            <Avatar name={contact.name} size="sm" />
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-slate-900">
                                    {contact.name}
                                </p>
                                <p className="truncate text-[11px] text-slate-500">
                                    {statusLabel(contact.role)}
                                </p>
                            </div>
                        </div>
                    )}
                </div>

                <Stem />
                <LevelLabel>Crew Hub workforce</LevelLabel>
                <Stem />

                <button
                    type="button"
                    onClick={onOpenWorkforce}
                    className="mx-auto flex w-full max-w-[280px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left shadow-card transition hover:border-brand hover:bg-brand-soft/40"
                >
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-soft text-success">
                        <Users className="h-5 w-5" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-base font-semibold text-slate-900">
                            {formatNumber(workforceCount)} workers
                        </p>
                        <p className="truncate text-[11px] text-slate-500">
                            Reports upward to connected manager(s)
                        </p>
                    </div>
                </button>

                <p className="mt-5 flex items-start gap-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                    <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                    Workers in this Crew Hub report upward through the connected manager(s) to the
                    major project.
                </p>
            </div>
        </Card>
    );
}
