import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import { cn } from '@/utils/helpers';

const TONES = {
    brand: 'text-brand',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
    journey: 'text-journey',
};

export default function OperationalSummaryCard({ summary, icon: Icon, href, tone = 'brand' }) {
    const items = summary?.items || [];

    return (
        <Card
            className="flex h-full flex-col p-3"
            padding={false}
            title={
                <span className="flex items-center gap-2">
                    {Icon && <Icon className={cn('h-4 w-4 shrink-0', TONES[tone] || TONES.brand)} />}
                    {summary?.title || 'Summary'}
                </span>
            }
        >
            <ul className="flex-1 space-y-1.5">
                {items.length === 0 && (
                    <li className="text-xs text-slate-500">No items</li>
                )}
                {items.map((item) => (
                    <li key={item.label} className="flex items-center justify-between gap-3 text-xs">
                        <span className="text-slate-500">{item.label}</span>
                        <span className={cn('font-medium', TONES[item.tone] || 'text-slate-900')}>
                            {item.value}
                        </span>
                    </li>
                ))}
            </ul>

            {href && (
                <Link
                    href={href}
                    className="mt-3 inline-flex items-center gap-1 border-t border-slate-100 pt-2 text-xs font-medium text-brand hover:text-brand-hover"
                >
                    View details
                    <ArrowRight className="h-3.5 w-3.5" />
                </Link>
            )}
        </Card>
    );
}
