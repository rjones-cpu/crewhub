import { router, useForm } from '@inertiajs/react';
import { Download, Pencil, Plus, Search, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Shared/Pagination';
import SettingsLayout from '@/Layouts/SettingsLayout';
import { cn, unwrapPaginated } from '@/utils/helpers';

const emptyForm = {
    name: '',
    code: '',
    description: '',
    is_active: true,
};

export default function SettingsPositions({
    positions = [],
    filters = {},
}) {
    const { items, links, meta } = unwrapPaginated(positions);
    const fileInputRef = useRef(null);
    const [search, setSearch] = useState(filters.search || '');
    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);

    const form = useForm(emptyForm);
    const importForm = useForm({
        file: null,
    });

    useEffect(() => {
        setSearch(filters.search || '');
    }, [filters.search]);

    const applyFilters = (next = {}) => {
        router.get(
            route('settings.positions.index'),
            {
                search: search || undefined,
                status: filters.status || 'all',
                per_page: filters.per_page || 10,
                ...next,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const openCreate = () => {
        setEditingId(null);
        setShowForm(true);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm);
    };

    const openEdit = (position) => {
        setEditingId(position.id);
        setShowForm(true);
        form.clearErrors();
            form.setData({
                name: position.name || '',
                code: position.code || '',
                description: position.description || '',
                is_active: Boolean(position.is_active),
            });
    };

    const submitForm = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setShowForm(false);
                setEditingId(null);
                form.reset();
                form.setData(emptyForm);
            },
        };

        if (editingId) {
            form.put(route('settings.positions.update', editingId), options);

            return;
        }

        form.post(route('settings.positions.store'), options);
    };

    const submitImport = (event) => {
        event.preventDefault();
        importForm.post(route('settings.positions.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                importForm.reset();
                importForm.setData({ file: null });
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    return (
        <SettingsLayout
            title="Settings"
            pageTitle="Positions"
            subtitle="Maintain the role catalog used when adding workers."
        >
            <div className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold text-slate-900">Positions</h1>
                        <p className="mt-0.5 text-xs text-slate-500">
                            Shared catalog for every company. Worker forms use this list for Role / Position.
                        </p>
                    </div>
                    <button type="button" onClick={openCreate} className="btn-primary min-h-9 px-3 text-xs">
                        <Plus className="h-3.5 w-3.5" />
                        Add position
                    </button>
                </div>

                <form onSubmit={submitImport} className="card p-4">
                    <h2 className="text-sm font-semibold text-slate-900">Upload CSV</h2>
                    <p className="mt-1 text-xs text-slate-500">
                        Columns: <span className="font-medium text-slate-700">name</span> (required),
                        {' '}code, description. Matching names in the shared catalog are updated.
                    </p>
                    <div className="mt-3 flex flex-wrap items-end gap-2">
                        <label className="min-w-[220px] flex-1 text-xs font-medium text-slate-700">
                            CSV file
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".csv,text/csv"
                                className="mt-1 block w-full text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-brand-soft file:px-3 file:py-2 file:text-xs file:font-medium file:text-brand"
                                onChange={(event) => importForm.setData('file', event.target.files?.[0] || null)}
                            />
                            {importForm.errors.file && (
                                <span className="mt-1 block text-xs text-danger">{importForm.errors.file}</span>
                            )}
                        </label>
                        <button
                            type="submit"
                            disabled={importForm.processing || ! importForm.data.file}
                            className="btn-secondary min-h-10 px-3 text-xs"
                        >
                            <Upload className="h-3.5 w-3.5" />
                            Import
                        </button>
                        <a
                            href={route('settings.positions.template')}
                            className="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-3 text-xs font-medium text-slate-600 hover:bg-slate-100"
                        >
                            <Download className="h-3.5 w-3.5" />
                            Template
                        </a>
                    </div>
                </form>

                {showForm && (
                    <form onSubmit={submitForm} className="card p-4">
                        <h2 className="text-sm font-semibold text-slate-900">
                            {editingId ? 'Edit position' : 'New position'}
                        </h2>
                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                            <label className="text-xs font-medium text-slate-700">
                                Name
                                <input
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    className="input-field mt-1 min-h-10"
                                    required
                                />
                                {form.errors.name && (
                                    <span className="mt-1 block text-xs text-danger">{form.errors.name}</span>
                                )}
                            </label>
                            <label className="text-xs font-medium text-slate-700">
                                Code
                                <input
                                    value={form.data.code}
                                    onChange={(event) => form.setData('code', event.target.value)}
                                    className="input-field mt-1 min-h-10"
                                />
                                {form.errors.code && (
                                    <span className="mt-1 block text-xs text-danger">{form.errors.code}</span>
                                )}
                            </label>
                            <label className="text-xs font-medium text-slate-700 sm:col-span-2">
                                Description
                                <input
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    className="input-field mt-1 min-h-10"
                                />
                                {form.errors.description && (
                                    <span className="mt-1 block text-xs text-danger">{form.errors.description}</span>
                                )}
                            </label>
                            <label className="flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(event) => form.setData('is_active', event.target.checked)}
                                />
                                Active
                            </label>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button type="submit" disabled={form.processing} className="btn-primary min-h-9 px-3 text-xs">
                                {editingId ? 'Save changes' : 'Add position'}
                            </button>
                            <button
                                type="button"
                                className="btn-secondary min-h-9 px-3 text-xs"
                                onClick={() => {
                                    setShowForm(false);
                                    setEditingId(null);
                                }}
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                )}

                <div className="card overflow-hidden">
                    <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3">
                        <div className="relative min-w-[180px] flex-1">
                            <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        event.preventDefault();
                                        applyFilters({ search: event.target.value || undefined });
                                    }
                                }}
                                placeholder="Search positions..."
                                className="input-field min-h-9 pl-8 text-xs"
                            />
                        </div>
                        <select
                            value={filters.status || 'all'}
                            onChange={(event) => applyFilters({ status: event.target.value })}
                            className="input-field min-h-9 w-32 text-xs"
                            aria-label="Filter by status"
                        >
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button
                            type="button"
                            className="btn-secondary min-h-9 px-3 text-xs"
                            onClick={() => applyFilters({ search: search || undefined })}
                        >
                            Search
                        </button>
                    </div>

                    {items.length === 0 ? (
                        <EmptyState
                            title="No positions yet"
                            description="Add a position or import a CSV to start the catalog."
                        />
                    ) : (
                        <>
                        <div className="table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((position) => (
                                        <tr key={position.id}>
                                            <td>
                                                <div className="font-medium text-slate-900">{position.name}</div>
                                                {position.description && (
                                                    <div className="text-[11px] text-slate-500">{position.description}</div>
                                                )}
                                            </td>
                                            <td className="text-slate-600">{position.code || '—'}</td>
                                            <td>
                                                <span
                                                    className={cn(
                                                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                                        position.is_active
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : 'bg-slate-100 text-slate-500',
                                                    )}
                                                >
                                                    {position.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="text-right">
                                                <button
                                                    type="button"
                                                    className="mr-2 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
                                                    onClick={() => openEdit(position)}
                                                >
                                                    <Pencil className="h-3 w-3" />
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    className="inline-flex items-center gap-1 text-xs font-medium text-rose-600 hover:underline"
                                                    onClick={() => {
                                                        if (confirm(`Remove ${position.name}? Workers keep the current title.`)) {
                                                            router.delete(route('settings.positions.destroy', position.id), {
                                                                preserveScroll: true,
                                                            });
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="h-3 w-3" />
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-3">
                            <Pagination
                                links={links}
                                meta={meta}
                                compact
                                itemLabel="positions"
                                perPage={Number(filters.per_page || 10)}
                                perPageOptions={[10, 25, 50, 100]}
                                onPerPageChange={(perPage) => applyFilters({ per_page: perPage })}
                            />
                        </div>
                        </>
                    )}
                </div>
            </div>
        </SettingsLayout>
    );
}
