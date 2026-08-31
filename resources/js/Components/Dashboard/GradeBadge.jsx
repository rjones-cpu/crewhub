import { cn } from '@/utils/helpers';

// Shared palette for every A–D grade on the dashboard, including the donut,
// which needs the raw hex because Recharts cells cannot take Tailwind classes.
export const GRADE_STYLES = {
    A: { solid: 'bg-success text-white', text: 'text-success', dot: 'bg-success', hex: '#16A34A' },
    B: { solid: 'bg-amber-500 text-white', text: 'text-amber-500', dot: 'bg-amber-500', hex: '#F59E0B' },
    C: { solid: 'bg-orange-500 text-white', text: 'text-orange-500', dot: 'bg-orange-500', hex: '#F97316' },
    D: { solid: 'bg-danger text-white', text: 'text-danger', dot: 'bg-danger', hex: '#DC2626' },
    '—': { solid: 'bg-slate-200 text-slate-600', text: 'text-slate-500', dot: 'bg-slate-400', hex: '#94A3B8' },
    NA: { solid: 'bg-slate-200 text-slate-600', text: 'text-slate-500', dot: 'bg-slate-400', hex: '#94A3B8' },
};

const SIZES = {
    xs: 'h-4 w-4 text-[9px]',
    sm: 'h-5 w-5 text-[10px]',
    md: 'h-6 w-6 text-[11px]',
    lg: 'h-11 w-11 text-xl',
};

export function gradeStyle(grade) {
    return GRADE_STYLES[grade] || GRADE_STYLES.NA;
}

export default function GradeBadge({ grade, size = 'sm', variant = 'solid', className = '' }) {
    const display = !grade || grade === 'NA' ? '—' : grade;
    const style = gradeStyle(display);

    if (variant === 'text') {
        return <span className={cn('font-semibold', style.text, className)}>{display}</span>;
    }

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-full font-bold',
                SIZES[size] || SIZES.sm,
                style.solid,
                className,
            )}
        >
            {display}
        </span>
    );
}
