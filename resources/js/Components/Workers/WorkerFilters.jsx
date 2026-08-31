import { router } from '@inertiajs/react';
import { ChevronDown, Search, SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import useDebounce from '@/hooks/useDebounce';

const controlClass =
    'h-8 w-full rounded border border-slate-200 bg-white px-2.5 text-[9px] text-slate-700 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';

// bg-none drops the forms-plugin caret, which the horizontal padding above
// would otherwise let long option labels run underneath.
const selectClass = `${controlClass} appearance-none truncate bg-none py-0 pr-7 leading-none`;

function FilterSelect({ label, value, onChange, children }) {
    return (
        <div className="relative">
            <select aria-label={label} className={selectClass} value={value} onChange={onChange}>
                {children}
            </select>
            <ChevronDown className="pointer-events-none absolute right-2 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" />
        </div>
    );
}

export default function WorkerFilters({ filters = {}, filterOptions = {}, projects = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const debouncedSearch = useDebounce(search, 300);

    const applyFilters = (overrides = {}) => {
        router.get(
            route('workers.index'),
            {
                search: debouncedSearch || undefined,
                status: filters.status || undefined,
                position: filters.position || undefined,
                location: filters.location || undefined,
                project_id: filters.project_id || undefined,
                ...overrides,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    useEffect(() => {
        if ((filters.search || '') === (debouncedSearch || '')) {
            return;
        }

        applyFilters({ search: debouncedSearch || undefined });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedSearch]);

    return (
        <div className="grid gap-2 md:grid-cols-[minmax(180px,1.25fr)_repeat(3,minmax(120px,0.75fr))_auto]">
            <div className="relative">
                <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search workers..."
                    className={`${controlClass} pl-8 pr-7`}
                />
                {search && (
                    <button
                        type="button"
                        onClick={() => setSearch('')}
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400"
                        aria-label="Clear search"
                    >
                        <X className="h-3 w-3" />
                    </button>
                )}
            </div>
            <FilterSelect
                label="Filter by status"
                value={filters.status || ''}
                onChange={(e) => applyFilters({ status: e.target.value || undefined, search: debouncedSearch || undefined })}
            >
                <option value="">All Status</option>
                {(filterOptions.statuses || []).map((status) => (
                    <option key={status} value={status}>{status.replace(/_/g, ' ')}</option>
                ))}
            </FilterSelect>
            <FilterSelect
                label="Filter by project"
                value={filters.project_id || ''}
                onChange={(e) => applyFilters({ project_id: e.target.value || undefined, search: debouncedSearch || undefined })}
            >
                <option value="">All Projects</option>
                {projects.map((project) => (
                    <option key={project.id} value={project.id}>{project.name}</option>
                ))}
            </FilterSelect>
            <FilterSelect
                label="Filter by role"
                value={filters.position || ''}
                onChange={(e) => applyFilters({ position: e.target.value || undefined, search: debouncedSearch || undefined })}
            >
                <option value="">All Roles</option>
                {(filterOptions.positions || []).map((position) => (
                    <option key={position.value || position} value={position.value || position}>
                        {position.label || position}
                    </option>
                ))}
            </FilterSelect>
            <button
                type="button"
                className="inline-flex h-8 items-center justify-center gap-1.5 rounded border border-slate-200 bg-white px-3 text-[9px] font-medium text-slate-700 hover:bg-slate-50"
            >
                <SlidersHorizontal className="h-3 w-3" />
                Filters
            </button>
        </div>
    );
}
