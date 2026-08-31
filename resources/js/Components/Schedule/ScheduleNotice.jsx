import { Info } from 'lucide-react';
import { cn } from '@/utils/helpers';

const TONES = {
    info: 'border-slate-200 bg-slate-50 text-slate-500',
    brand: 'border-brand/20 bg-brand-soft text-slate-600',
};

/**
 * The explanatory boxes on the Schedule page: the overtime approval rule beside
 * the change-request KPIs, and the footnotes that list where the data is pulled
 * from.
 */
export default function ScheduleNotice({ title, children, tone = 'info', className = '' }) {
    return (
        <div className={cn('flex items-start gap-2 rounded-xl border px-3 py-2', TONES[tone] || TONES.info, className)}>
            <Info className={cn('mt-px h-3.5 w-3.5 shrink-0', tone === 'brand' ? 'text-brand' : 'text-slate-400')} />
            <div className="min-w-0 text-[10px] leading-relaxed">
                {title && <p className="font-semibold text-slate-800">{title}</p>}
                <div className={title ? 'mt-0.5' : undefined}>{children}</div>
            </div>
        </div>
    );
}
