import { ArrowRight, Download, Zap } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import PanelTitle from './PanelTitle';

export default function QuickExportsPanel({ templates = [], onExport, onViewAll }) {
    return (
        <Card
            title={<PanelTitle icon={Zap}>Quick Exports</PanelTitle>}
            className="flex h-full flex-col"
        >
            <ul className="flex-1 space-y-1">
                {templates.map((item) => (
                    <li key={item.id}>
                        <button
                            type="button"
                            onClick={() => onExport?.(item)}
                            className="flex w-full items-center gap-2 rounded-lg px-1 py-2 text-left text-xs text-slate-700 transition hover:bg-slate-50 hover:text-brand"
                        >
                            <Download className="h-3.5 w-3.5 shrink-0 text-brand" />
                            <span className="min-w-0 truncate">{item.name}</span>
                        </button>
                    </li>
                ))}
            </ul>

            <button
                type="button"
                onClick={onViewAll}
                className="mt-3 inline-flex items-center gap-1 self-start text-xs font-medium text-brand hover:underline"
            >
                View all export templates
                <ArrowRight className="h-3 w-3" />
            </button>
        </Card>
    );
}
