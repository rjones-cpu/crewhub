import { Info } from 'lucide-react';

export default function PanelTitle({ icon: Icon, info = false, children }) {
    return (
        <span className="flex items-center gap-2">
            {Icon && <Icon className="h-4 w-4 shrink-0 text-brand" />}
            <span>{children}</span>
            {info && <Info className="h-3.5 w-3.5 shrink-0 text-slate-400" />}
        </span>
    );
}
