import { cn } from '@/utils/helpers';

export default function LoadingSpinner({ className = '', size = 'md' }) {
    const sizeClass = {
        sm: 'h-4 w-4 border-2',
        md: 'h-8 w-8 border-2',
        lg: 'h-12 w-12 border-4',
    }[size];

    return (
        <div className={cn('flex items-center justify-center py-8', className)}>
            <div
                className={cn(
                    'animate-spin rounded-full border-brand border-t-transparent',
                    sizeClass,
                )}
                role="status"
                aria-label="Loading"
            />
        </div>
    );
}
