import { cn } from '@/utils/helpers';

const SIZES = {
    sm: { track: 'h-5 w-9', knob: 'h-4 w-4', shift: 'translate-x-4' },
    md: { track: 'h-6 w-11', knob: 'h-5 w-5', shift: 'translate-x-5' },
};

export default function ToggleSwitch({
    checked = false,
    onChange,
    disabled = false,
    readOnly = false,
    label,
    size = 'md',
    className = '',
}) {
    const dimensions = SIZES[size] || SIZES.md;

    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            aria-readonly={readOnly || undefined}
            disabled={disabled || readOnly}
            onClick={() => onChange?.(!checked)}
            className={cn(
                'relative inline-flex shrink-0 rounded-full transition',
                dimensions.track,
                checked ? 'bg-brand' : 'bg-slate-200',
                disabled && !readOnly && 'cursor-not-allowed opacity-50',
                !disabled && !readOnly && 'cursor-pointer',
                readOnly && 'cursor-default',
                className,
            )}
        >
            <span
                className={cn(
                    'absolute left-0.5 top-0.5 rounded-full bg-white shadow transition',
                    dimensions.knob,
                    checked && dimensions.shift,
                )}
            />
        </button>
    );
}
