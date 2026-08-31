import Card from '@/Components/Shared/Card';
export default function ReadinessByCategory({ categories = [] }) {
    return (
        <Card padding={false} className="rounded-lg p-3">
            <div className="mb-3 grid grid-cols-[1fr_repeat(3,54px)] items-end gap-2">
                <h3 className="text-[10px] font-bold text-slate-800">Readiness by Category</h3>
                <span className="text-center text-[7px] font-semibold text-slate-500">Ready</span>
                <span className="text-center text-[7px] font-semibold text-slate-500">At Risk</span>
                <span className="text-center text-[7px] font-semibold text-slate-500">Not Ready</span>
            </div>
            {categories.length === 0 ? (
                <p className="py-8 text-center text-sm text-slate-500">No category data</p>
            ) : (
                <div className="space-y-3">
                    {categories.map((category) => (
                        <div key={category.name} className="grid grid-cols-[1fr_repeat(3,54px)] items-center gap-2">
                            <div className="min-w-0">
                                <p className="mb-1 truncate text-[8px] font-semibold text-slate-700">
                                    {category.name}
                                </p>
                                <div className="flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        className="bg-success"
                                        style={{ width: `${Number(category.ready_pct) || 0}%` }}
                                    />
                                    <div
                                        className="bg-warning"
                                        style={{ width: `${Number(category.at_risk_pct) || 0}%` }}
                                    />
                                    <div
                                        className="bg-danger"
                                        style={{ width: `${Number(category.not_ready_pct) || 0}%` }}
                                    />
                                </div>
                            </div>
                            <span className="text-center text-[7px] text-slate-600">
                                {category.ready ?? 0} ({category.ready_pct ?? 0}%)
                            </span>
                            <span className="text-center text-[7px] text-slate-600">
                                {category.at_risk ?? 0} ({category.at_risk_pct ?? 0}%)
                            </span>
                            <span className="text-center text-[7px] text-slate-600">
                                {category.not_ready ?? 0} ({category.not_ready_pct ?? 0}%)
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </Card>
    );
}
