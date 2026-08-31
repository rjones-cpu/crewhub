import { cn } from '@/utils/helpers';

export function Section({ index, title, children, className }) {
    return (
        <section className={cn('card p-4', className)}>
            <h2 className="text-xs font-semibold text-slate-900">
                {index}. {title}
            </h2>
            <div className="mt-3">{children}</div>
        </section>
    );
}

export function Field({ label, required = false, error, hint, htmlFor, children, className }) {
    return (
        <div className={cn('min-w-0', className)}>
            <label htmlFor={htmlFor} className="block text-[11px] font-medium text-slate-700">
                {label}
                {required && <span className="ml-0.5 text-danger">*</span>}
            </label>
            <div className="mt-1">{children}</div>
            {error ? (
                <p className="mt-1 text-[11px] text-danger">{error}</p>
            ) : hint ? (
                <p className="mt-1 text-[11px] text-slate-400">{hint}</p>
            ) : null}
        </div>
    );
}

export function TextInput({ id, error, className, ...props }) {
    return (
        <input
            id={id}
            className={cn(
                'input-field h-9 py-0 text-xs',
                error && 'border-danger focus:border-danger focus:ring-danger',
                className,
            )}
            {...props}
        />
    );
}

export function SelectInput({ id, error, placeholder, options = [], className, ...props }) {
    return (
        <select
            id={id}
            className={cn(
                'input-field h-9 py-0 text-xs',
                error && 'border-danger focus:border-danger focus:ring-danger',
                className,
            )}
            {...props}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export function TextArea({ id, error, className, ...props }) {
    return (
        <textarea
            id={id}
            className={cn(
                'input-field text-xs',
                error && 'border-danger focus:border-danger focus:ring-danger',
                className,
            )}
            {...props}
        />
    );
}
