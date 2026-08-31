import { cn } from '@/utils/helpers';

const variants = {
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    danger: 'inline-flex items-center justify-center gap-2 rounded-lg bg-danger px-3.5 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-danger/30 disabled:opacity-50',
    ghost: 'inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-50',
};

export default function Button({
    as: Component = 'button',
    variant = 'primary',
    className = '',
    type,
    children,
    ...props
}) {
    return (
        <Component
            type={Component === 'button' ? type || 'button' : type}
            className={cn('min-h-10', variants[variant] || variants.primary, className)}
            {...props}
        >
            {children}
        </Component>
    );
}
