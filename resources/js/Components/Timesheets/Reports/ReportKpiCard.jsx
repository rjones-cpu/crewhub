import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    Sparkles,
    Timer,
    Users,
} from 'lucide-react';
import { cn } from '@/utils/helpers';

const ICONS = {
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    Sparkles,
    Timer,
    Users,
};

const TONES = {
    brand: 'bg-brand-soft text-brand',
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    journey: 'bg-journey-soft text-journey',
    sky: 'bg-sky-50 text-sky-600',
    cyan: 'bg-cyan-50 text-cyan-600',
};

export default function ReportKpiCard({ label, value, hint, href, icon, tone = 'brand' }) {
    const Icon = ICONS[icon] || FileSpreadsheet;

    return (
        <div className="card card-padding">
            <div className="flex items-start gap-3">
                <span
                    className={cn(
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                        TONES[tone] || TONES.brand,
                    )}
                >
                    <Icon className="h-5 w-5" />
                </span>
                <div className="min-w-0">
                    <p className="truncate text-xs font-medium text-slate-500">{label}</p>
                    <p className="mt-0.5 text-2xl font-semibold leading-tight text-slate-900">
                        {value}
                    </p>
                </div>
            </div>

            {hint && (
                href ? (
                    <Link
                        href={href.includes('/') ? href : route(href)}
                        className="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
                    >
                        {hint}
                        <ArrowRight className="h-3 w-3" />
                    </Link>
                ) : (
                    <p className="mt-3 text-xs text-slate-500">{hint}</p>
                )
            )}
        </div>
    );
}
