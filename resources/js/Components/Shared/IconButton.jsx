import { cn } from '@/utils/helpers';

export default function IconButton({
    children,
    label,
    className = '',
    variant = 'ghost',
    ...props
}) {
    const variants = {
        ghost: 'text-slate-500 hover:bg-slate-100 hover:text-slate-800',
        solid: 'bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50',
        sidebar: 'text-sidebar-muted hover:bg-sidebar-light hover:text-white',
    };

    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            className={cn(
                'inline-flex h-10 w-10 items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-brand/30',
                variants[variant] || variants.ghost,
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
