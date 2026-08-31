import { ArrowRight, CheckCircle2, Clock, Sparkles, Undo2, Users } from 'lucide-react';
import { formatNumber } from '@/utils/formatters';
import { cn } from '@/utils/helpers';

const ICONS = { CheckCircle2, Clock, Sparkles, Undo2, Users };

const TONES = {
    warning: 'bg-warning-soft text-warning',
    journey: 'bg-journey-soft text-journey',
    sky: 'bg-sky-50 text-sky-600',
    danger: 'bg-danger-soft text-danger',
    success: 'bg-success-soft text-success',
};

export default function QueueStatCard({ label, value, icon, tone = 'warning', onView }) {
    const Icon = ICONS[icon] || Clock;

    return (
        <div className="card flex min-h-[74px] flex-col rounded-lg p-3">
            <div className="flex items-start gap-2.5">
                <span
                    className={cn(
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                        TONES[tone] || TONES.warning,
                    )}
                >
                    <Icon className="h-4 w-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-[10px] font-semibold leading-tight text-slate-500">{label}</p>
                    <p className="mt-1 text-xl font-semibold leading-none text-slate-900">
                        {formatNumber(value)}
                    </p>
                </div>
            </div>

            <button
                type="button"
                onClick={onView}
                className="mt-auto inline-flex items-center gap-1 self-end text-[10px] font-semibold text-brand hover:underline"
            >
                View
                <ArrowRight className="h-2.5 w-2.5" />
            </button>
        </div>
    );
}
