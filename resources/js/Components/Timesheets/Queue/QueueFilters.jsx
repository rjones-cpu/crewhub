import { router, usePage } from '@inertiajs/react';
import { CalendarDays, Download, RefreshCw, SlidersHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import Button from '@/Components/Shared/Button';

function Field({ label, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-[10px] font-semibold text-slate-500">{label}</span>
            {children}
        </label>
    );
}

export default function QueueFilters({ filters, onFilter, onRunCheck, onExport }) {
    const { majorProjects = [], currentProject } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    // Debounce free-text search so each keystroke does not hit the server.
    useEffect(() => {
        if ((filters.search ?? '') === search) {
            return undefined;
        }

        const timer = setTimeout(() => onFilter({ search }), 350);

        return () => clearTimeout(timer);
    }, [search]);

    const switchProject = (value) => {
        if (value === 'all') {
            router.post(route('major-projects.clear'), {}, { preserveState: true });

            return;
        }

        router.post(route('major-projects.switch', value), {}, { preserveState: true });
    };

    return (
        <div className="card rounded-lg p-3">
            <div className="flex flex-wrap items-end gap-2">
                <Field label="Major Project" className="min-w-[135px] flex-1">
                    <select
                        className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                        value={currentProject?.id ?? 'all'}
                        onChange={(event) => switchProject(event.target.value)}
                    >
                        <option value="all">All Projects</option>
                        {majorProjects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field label="Week" className="min-w-[135px] flex-1">
                    <div className="relative">
                        <select
                            className="input-field !h-8 appearance-none !rounded-md !py-0 !pr-8 !text-[11px]"
                            value={filters.week}
                            onChange={(event) => onFilter({ week: event.target.value })}
                        >
                            {filters.options.weeks.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <CalendarDays className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    </div>
                </Field>

                <div className="relative min-w-[180px] flex-[1.35] self-end">
                    <input
                        type="search"
                        className="input-field !h-8 !rounded-md !py-0 !pr-8 !text-[11px]"
                        placeholder="Search worker name or timesheet ID..."
                        aria-label="Search worker name or timesheet ID"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <SlidersHorizontal className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                </div>

                <Field label="Status" className="min-w-[115px] flex-1">
                    <select
                        className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                        value={filters.status}
                        onChange={(event) => onFilter({ status: event.target.value })}
                    >
                        {filters.options.statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field label="Approver Role" className="min-w-[115px] flex-1">
                    <select
                        className="input-field !h-8 !rounded-md !py-0 !text-[11px]"
                        value={filters.approver_role}
                        onChange={(event) => onFilter({ approver_role: event.target.value })}
                    >
                        {filters.options.approverRoles.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </Field>

                <div className="flex items-center gap-1.5 self-end">
                    <Button
                        variant="secondary"
                        className="!min-h-8 !rounded-md !px-2.5 !py-0 !text-[10px]"
                        onClick={onRunCheck}
                    >
                        <RefreshCw className="h-3.5 w-3.5 text-brand" />
                        Run Approval Check
                    </Button>
                    <Button
                        variant="secondary"
                        className="!min-h-8 !rounded-md !px-2.5 !py-0 !text-[10px]"
                        onClick={onExport}
                    >
                        <Download className="h-3.5 w-3.5" />
                        Export
                    </Button>
                </div>
            </div>
        </div>
    );
}
