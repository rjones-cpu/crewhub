import { ChevronRight, Library } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import PanelTitle from './PanelTitle';

export default function ReportLibraryPanel({ reports = [], onSelect }) {
    return (
        <Card title={<PanelTitle icon={Library}>Report Library</PanelTitle>} className="h-full">
            <ul className="divide-y divide-slate-100">
                {reports.map((report) => (
                    <li key={report.id}>
                        <button
                            type="button"
                            onClick={() => onSelect?.(report)}
                            className="flex w-full items-center justify-between gap-2 py-2.5 text-left text-xs text-slate-700 transition hover:text-brand"
                        >
                            <span className="min-w-0 truncate">{report.name}</span>
                            <ChevronRight className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        </button>
                    </li>
                ))}
            </ul>
        </Card>
    );
}
