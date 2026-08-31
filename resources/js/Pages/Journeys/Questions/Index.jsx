import { Head, router } from '@inertiajs/react';
import {
    ArrowDownUp,
    Check,
    GripVertical,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import JourneySubnav from '@/Components/Journeys/JourneySubnav';
import ListingPager, { PER_PAGE_OPTIONS } from '@/Components/Journeys/ListingPager';
import QuestionFormPanel from '@/Components/Journeys/Questions/QuestionFormPanel';
import EmptyState from '@/Components/Shared/EmptyState';
import AppLayout from '@/Layouts/AppLayout';
import { formatNumber } from '@/utils/formatters';
import { cn, unwrapPaginated } from '@/utils/helpers';

const TABS = [
    { key: 'all', label: 'All Questions' },
    { key: 'library', label: 'Question Library' },
];

function LibraryTab({ templates = [], canManage }) {
    const [adding, setAdding] = useState(null);

    const addTemplate = (template) => {
        setAdding(template.key);
        router.post(
            route('journeys.questions.store'),
            {
                type: template.type,
                question: template.question,
                description: template.description,
                options: template.options,
                risk_key: template.risk_key,
                risk_weight: template.risk_weight,
                is_required: template.is_required,
                is_active: true,
            },
            { preserveScroll: true, onFinish: () => setAdding(null) },
        );
    };

    return (
        <div className="grid gap-2.5 p-4 sm:grid-cols-2">
            {templates.map((template) => (
                <div
                    key={template.key}
                    className="flex items-start justify-between gap-3 rounded-lg border border-slate-200 p-3"
                >
                    <div className="min-w-0">
                        <p className="text-xs font-medium text-slate-800">{template.question}</p>
                        <p className="mt-0.5 text-[11px] leading-snug text-slate-500">
                            {template.description}
                        </p>
                        <p className="mt-1.5 text-[11px] text-slate-400">
                            Type: {template.type_label}
                            {template.risk_key && ` · Feeds ${template.risk_key.replace(/_/g, ' ')}`}
                        </p>
                    </div>
                    {canManage && (
                        <button
                            type="button"
                            disabled={template.already_added || adding === template.key}
                            onClick={() => addTemplate(template)}
                            className={cn(
                                'inline-flex shrink-0 items-center gap-1 rounded-md px-2.5 py-1.5 text-[11px] font-medium transition',
                                template.already_added
                                    ? 'cursor-not-allowed bg-slate-100 text-slate-400'
                                    : 'border border-brand/40 text-brand hover:bg-brand-soft',
                            )}
                        >
                            {template.already_added ? (
                                <>
                                    <Check className="h-3 w-3" />
                                    Added
                                </>
                            ) : (
                                <>
                                    <Plus className="h-3 w-3" />
                                    Add
                                </>
                            )}
                        </button>
                    )}
                </div>
            ))}
        </div>
    );
}

export default function JourneyQuestionsIndex({
    questions,
    library = [],
    questionTypes = [],
    filters = {},
    canManage = false,
}) {
    const { items, links, meta } = unwrapPaginated(questions);
    const pageLinks = Array.isArray(links) ? links : meta?.links ?? [];

    const [tab, setTab] = useState('all');
    const [search, setSearch] = useState(filters.search || '');
    const [editing, setEditing] = useState(null);
    const [showPanel, setShowPanel] = useState(false);
    const [reordering, setReordering] = useState(false);
    const [order, setOrder] = useState(items);
    const [dragIndex, setDragIndex] = useState(null);

    useEffect(() => {
        setOrder(items);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [questions]);

    const applyFilters = (next = {}) => {
        router.get(
            route('journeys.questions'),
            {
                search: next.search ?? search,
                type: next.type ?? filters.type ?? '',
                status: next.status ?? filters.status ?? 'active',
                per_page: next.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0],
            },
            { preserveState: true, replace: true },
        );
    };

    const openCreate = () => {
        setEditing(null);
        setShowPanel(true);
    };

    const openEdit = (question) => {
        setEditing(question);
        setShowPanel(true);
    };

    const destroy = (question) => {
        if (window.confirm(`Delete "${question.question}"?`)) {
            router.delete(route('journeys.questions.destroy', question.id), { preserveScroll: true });
        }
    };

    const handleDrop = (targetIndex) => {
        if (dragIndex === null || dragIndex === targetIndex) {
            return;
        }

        const next = [...order];
        const [moved] = next.splice(dragIndex, 1);
        next.splice(targetIndex, 0, moved);

        setOrder(next);
        setDragIndex(null);
        router.post(
            route('journeys.questions.reorder'),
            { ids: next.map((question) => question.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    const rows = reordering ? order : items;

    return (
        <AppLayout title="Journey Management" showMeta={false}>
            <Head title="Journey Questions" />

            <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                <JourneySubnav />

                <div className="min-w-0 flex-1 space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 className="text-lg font-semibold text-slate-900">Journey Questions</h1>
                            <p className="mt-0.5 text-xs text-slate-500">
                                Create and manage the questions that are asked to assess a journey.
                            </p>
                        </div>
                        {canManage && (
                            <button
                                type="button"
                                onClick={openCreate}
                                className="btn-primary min-h-9 px-3 text-xs"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Add Question
                            </button>
                        )}
                    </div>

                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div className="card min-w-0 flex-1">
                            <div className="flex items-center gap-4 border-b border-slate-100 px-4">
                                {TABS.map((item) => (
                                    <button
                                        key={item.key}
                                        type="button"
                                        onClick={() => setTab(item.key)}
                                        className={cn(
                                            'border-b-2 px-1 py-3 text-xs font-medium transition',
                                            tab === item.key
                                                ? 'border-brand text-brand'
                                                : 'border-transparent text-slate-500 hover:text-slate-800',
                                        )}
                                    >
                                        {item.label}
                                    </button>
                                ))}
                            </div>

                            {tab === 'library' ? (
                                <LibraryTab templates={library} canManage={canManage} />
                            ) : (
                                <>
                                    <div className="flex flex-wrap items-center gap-2 px-4 py-3">
                                        <div className="relative min-w-[200px] flex-1">
                                            <input
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                                placeholder="Search questions..."
                                                className="input-field h-9 py-0 pl-3 pr-8 text-xs"
                                            />
                                            <Search className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                        </div>
                                        <select
                                            className="input-field h-9 w-auto min-w-[150px] py-0 text-xs"
                                            value={filters.type || ''}
                                            onChange={(e) => applyFilters({ type: e.target.value, search })}
                                        >
                                            <option value="">All Question Types</option>
                                            {questionTypes.map((type) => (
                                                <option key={type.value} value={type.value}>
                                                    {type.label}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            className="input-field h-9 w-auto min-w-[110px] py-0 text-xs"
                                            value={filters.status || 'active'}
                                            onChange={(e) => applyFilters({ status: e.target.value, search })}
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="all">All</option>
                                        </select>
                                        {canManage && (
                                            <button
                                                type="button"
                                                onClick={() => setReordering((value) => !value)}
                                                className={cn(
                                                    'btn-secondary min-h-9 px-3 text-xs',
                                                    reordering && 'border-brand text-brand',
                                                )}
                                            >
                                                <ArrowDownUp className="h-3.5 w-3.5" />
                                                {reordering ? 'Done' : 'Reorder'}
                                            </button>
                                        )}
                                    </div>

                                    {rows.length === 0 ? (
                                        <div className="p-6">
                                            <EmptyState
                                                title="No questions yet"
                                                description="Add a question or adopt one from the question library."
                                                action={
                                                    canManage ? (
                                                        <button
                                                            type="button"
                                                            onClick={openCreate}
                                                            className="btn-primary inline-flex"
                                                        >
                                                            Add Question
                                                        </button>
                                                    ) : null
                                                }
                                            />
                                        </div>
                                    ) : (
                                        <ul className="divide-y divide-slate-100">
                                            {rows.map((question, index) => (
                                                <li
                                                    key={question.id}
                                                    draggable={reordering}
                                                    onDragStart={() => setDragIndex(index)}
                                                    onDragOver={(e) => reordering && e.preventDefault()}
                                                    onDrop={() => handleDrop(index)}
                                                    className={cn(
                                                        'flex items-center gap-3 px-4 py-3 transition',
                                                        reordering ? 'cursor-grab' : 'hover:bg-slate-50/70',
                                                        dragIndex === index && 'opacity-50',
                                                    )}
                                                >
                                                    <GripVertical
                                                        className={cn(
                                                            'h-4 w-4 shrink-0',
                                                            reordering ? 'text-slate-400' : 'text-slate-200',
                                                        )}
                                                    />
                                                    <span className="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-slate-200 text-[11px] font-medium text-slate-600">
                                                        {(meta?.from ?? 1) + index}
                                                    </span>

                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-xs font-medium text-slate-800">
                                                            {question.question}
                                                        </p>
                                                        {question.description && (
                                                            <p className="mt-0.5 truncate text-[11px] text-slate-500">
                                                                {question.description}
                                                            </p>
                                                        )}
                                                    </div>

                                                    <span className="hidden shrink-0 text-[11px] text-slate-500 sm:block">
                                                        Type: {question.type_label}
                                                    </span>

                                                    {canManage && (
                                                        <div className="flex shrink-0 items-center gap-1.5">
                                                            <button
                                                                type="button"
                                                                onClick={() => openEdit(question)}
                                                                className="inline-flex items-center gap-1 rounded-md border border-brand/40 px-2 py-1 text-[11px] font-medium text-brand transition hover:bg-brand-soft"
                                                            >
                                                                <Pencil className="h-3 w-3" />
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => destroy(question)}
                                                                className="inline-flex items-center gap-1 rounded-md border border-danger/40 px-2 py-1 text-[11px] font-medium text-danger transition hover:bg-danger-soft"
                                                            >
                                                                <Trash2 className="h-3 w-3" />
                                                                Delete
                                                            </button>
                                                        </div>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    )}

                                    {rows.length > 0 && (
                                        <div className="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <p className="text-[11px] text-slate-500">
                                                Showing {meta?.from ?? 0} to {meta?.to ?? rows.length} of{' '}
                                                {formatNumber(meta?.total ?? rows.length)} questions
                                            </p>
                                            <div className="flex items-center gap-2">
                                                <span className="text-[11px] text-slate-500">
                                                    Questions per page
                                                </span>
                                                <ListingPager
                                                    links={pageLinks}
                                                    perPage={
                                                        meta?.per_page ?? filters.per_page ?? PER_PAGE_OPTIONS[0]
                                                    }
                                                    onPerPageChange={(value) =>
                                                        applyFilters({ search, per_page: value })
                                                    }
                                                    onNavigate={(url) =>
                                                        url
                                                        && router.get(url, {}, {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        })
                                                    }
                                                />
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>

                        {canManage && showPanel && (
                            <QuestionFormPanel
                                question={editing}
                                questionTypes={questionTypes}
                                onCancel={() => setShowPanel(false)}
                                onSaved={() => {
                                    setShowPanel(false);
                                    setEditing(null);
                                }}
                            />
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
