import { Inbox } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function EmptyState({
    icon: Icon = Inbox,
    title = 'Nothing here yet',
    description = 'There is no data to display.',
    action,
    className = '',
}) {
    return (
        <div className={cn('flex flex-col items-center justify-center px-6 py-12 text-center', className)}>
            <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <Icon className="h-6 w-6" />
            </div>
            <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
            {description && <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
