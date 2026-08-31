import { cn } from '@/utils/helpers';

export default function Select({
    label,
    error,
    options = [],
    placeholder,
    className = '',
    id,
    ...props
}) {
    const selectId = id || props.name;

    return (
        <div className="w-full">
            {label && (
                <label htmlFor={selectId} className="mb-1.5 block text-sm font-medium text-slate-700">
                    {label}
                </label>
            )}
            <select
                id={selectId}
                className={cn('input-field min-h-10', error && 'border-danger focus:border-danger focus:ring-danger/30', className)}
                {...props}
            >
                {placeholder !== undefined && (
                    <option value="">{placeholder}</option>
                )}
                {options.map((option) => {
                    if (typeof option === 'string' || typeof option === 'number') {
                        return (
                            <option key={option} value={option}>
                                {option}
                            </option>
                        );
                    }

                    return (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    );
                })}
            </select>
            {error && <p className="mt-1.5 text-sm text-danger">{error}</p>}
        </div>
    );
}
