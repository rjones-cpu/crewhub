import { router, usePage } from '@inertiajs/react';
import { CalendarDays, Download, FileBarChart, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import Button from '@/Components/Shared/Button';

function Field({ label, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
            {children}
        </label>
    );
}

function Select({ filter, onChange }) {
    return (
        <select
            className="input-field"
            value={filter.selected}
            onChange={(event) => onChange(event.target.value)}
        >
            {filter.options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export default function ReportFilters({ filters, search: initialSearch = '', onGenerate, onExport }) {
    const { majorProjects = [], currentProject } = usePage().props;
    const [selection, setSelection] = useState({
        dateRange: filters.dateRange.selected,
        reportType: filters.reportType.selected,
        status: filters.status.selected,
        search: initialSearch,
    });

    useEffect(() => {
        setSelection({
            dateRange: filters.dateRange.selected,
            reportType: filters.reportType.selected,
            status: filters.status.selected,
            search: initialSearch,
        });
    }, [filters.dateRange.selected, filters.reportType.selected, filters.status.selected, initialSearch]);

    const update = (key) => (value) => setSelection((current) => ({ ...current, [key]: value }));

    const switchProject = (value) => {
        if (value === 'all') {
            router.post(route('major-projects.clear'), {}, { preserveState: true });

            return;
        }

        router.post(route('major-projects.switch', value), {}, { preserveState: true });
    };

    return (
        <div className="card card-padding">
            <div className="flex flex-wrap items-end gap-3">
                <Field label="Major Project" className="min-w-[170px] flex-1">
                    <select
                        className="input-field"
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

                <Field label="Week / Date Range" className="min-w-[170px] flex-1">
                    <div className="relative">
                        <select
                            className="input-field appearance-none pr-9"
                            value={selection.dateRange}
                            onChange={(event) => update('dateRange')(event.target.value)}
                        >
                            {filters.dateRange.options.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <CalendarDays className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    </div>
                </Field>

                <Field label="Report Type" className="min-w-[170px] flex-1">
                    <Select
                        filter={{ ...filters.reportType, selected: selection.reportType }}
                        onChange={update('reportType')}
                    />
                </Field>

                <Field label="Status" className="min-w-[150px] flex-1">
                    <Select
                        filter={{ ...filters.status, selected: selection.status }}
                        onChange={update('status')}
                    />
                </Field>

                <div className="relative min-w-[170px] flex-1 self-end">
                    <input
                        type="search"
                        className="input-field pr-9"
                        placeholder="Search worker or trade..."
                        aria-label="Search worker or trade"
                        value={selection.search}
                        onChange={(event) => update('search')(event.target.value)}
                    />
                    <Search className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                </div>

                <div className="flex items-center gap-2 self-end">
                    <Button onClick={() => onGenerate?.(selection)}>
                        <FileBarChart className="h-4 w-4" />
                        Generate Report
                    </Button>
                    <Button variant="secondary" onClick={() => onExport?.(selection)}>
                        <Download className="h-4 w-4" />
                        Export
                    </Button>
                </div>
            </div>
        </div>
    );
}
