import { cn } from '@/utils/helpers';

export default function Card({
    children,
    className = '',
    padding = true,
    title,
    actions,
}) {
    return (
        <div className={cn('card', padding && 'card-padding', className)}>
            {(title || actions) && (
                <div className="mb-4 flex items-start justify-between gap-3">
                    {title && <h3 className="section-title">{title}</h3>}
                    {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
                </div>
            )}
            {children}
        </div>
    );
}
