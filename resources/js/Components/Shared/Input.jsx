import { cn } from '@/utils/helpers';

export default function Input({
    label,
    error,
    className = '',
    id,
    ...props
}) {
    const inputId = id || props.name;

    return (
        <div className="w-full">
            {label && (
                <label htmlFor={inputId} className="mb-1.5 block text-sm font-medium text-slate-700">
                    {label}
                </label>
            )}
            <input
                id={inputId}
                className={cn('input-field min-h-10', error && 'border-danger focus:border-danger focus:ring-danger/30', className)}
                {...props}
            />
            {error && <p className="mt-1.5 text-sm text-danger">{error}</p>}
        </div>
    );
}
