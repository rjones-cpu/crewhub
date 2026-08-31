import { Search, X } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function SearchInput({
    value,
    onChange,
    onClear,
    placeholder = 'Search...',
    className = '',
    ...props
}) {
    return (
        <div className={cn('relative', className)}>
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                className="input-field min-h-10 pl-9 pr-9"
                {...props}
            />
            {value && onClear && (
                <button
                    type="button"
                    onClick={onClear}
                    className="absolute right-2 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Clear search"
                >
                    <X className="h-4 w-4" />
                </button>
            )}
        </div>
    );
}
