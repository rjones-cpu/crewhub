import {
    AlertTriangle,
    BadgeCheck,
    Plane,
    ShieldCheck,
    Users,
    XCircle,
} from 'lucide-react';
import { formatNumber, formatPercent } from '@/utils/formatters';

export default function ReadinessStats({ stats = {} }) {
    const cards = [
        {
            label: 'Total Workers',
            value: stats.total,
            hint: 'Across all projects',
            icon: Users,
            tone: 'bg-brand-soft text-brand',
        },
        {
            label: 'Ready Workforce',
            value: stats.ready,
            hint: `${formatPercent(stats.ready_pct)} of total`,
            icon: ShieldCheck,
            tone: 'bg-success-soft text-success',
        },
        {
            label: 'At Risk',
            value: stats.at_risk,
            hint: `${formatPercent(stats.total ? (stats.at_risk / stats.total) * 100 : 0)} of total`,
            icon: AlertTriangle,
            tone: 'bg-warning-soft text-warning',
        },
        {
            label: 'Not Ready',
            value: stats.not_ready,
            hint: `${formatPercent(stats.total ? (stats.not_ready / stats.total) * 100 : 0)} of total`,
            icon: XCircle,
            tone: 'bg-danger-soft text-danger',
        },
        {
            label: 'Certifications Expiring',
            value: stats.certs_expiring,
            hint: 'Within 30 days',
            icon: BadgeCheck,
            tone: 'bg-amber-50 text-amber-500',
        },
        {
            label: 'Journeys Pending',
            value: stats.journeys_pending,
            hint: 'Awaiting approval',
            icon: Plane,
            tone: 'bg-journey-soft text-journey',
        },
    ];

    return (
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            {cards.map((card) => {
                const Icon = card.icon;

                return (
                    <div key={card.label} className="card flex min-h-[76px] items-center gap-3 rounded-lg p-3">
                        <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${card.tone}`}>
                            <Icon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-[9px] font-semibold text-slate-700">{card.label}</p>
                            <p className="mt-0.5 text-xl font-bold leading-none text-slate-900">
                                {formatNumber(card.value ?? 0)}
                            </p>
                            <p className="mt-1 truncate text-[8px] text-slate-400">{card.hint}</p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
