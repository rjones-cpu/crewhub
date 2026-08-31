import { initials } from '@/utils/helpers';
import { cn } from '@/utils/helpers';

const sizes = {
    xs: 'h-5 w-5 text-[9px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-14 w-14 text-base',
};

export default function Avatar({ name = '', src, size = 'md', className = '' }) {
    if (src) {
        return (
            <img
                src={src}
                alt={name}
                className={cn(
                    'rounded-full object-cover ring-2 ring-white',
                    sizes[size] || sizes.md,
                    className,
                )}
            />
        );
    }

    return (
        <div
            className={cn(
                'inline-flex items-center justify-center rounded-full bg-brand-soft font-semibold text-brand ring-2 ring-white',
                sizes[size] || sizes.md,
                className,
            )}
            aria-label={name}
        >
            {initials(name)}
        </div>
    );
}
