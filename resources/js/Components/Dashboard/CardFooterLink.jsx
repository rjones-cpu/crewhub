import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function CardFooterLink({ href, children, className = '' }) {
    return (
        <Link
            href={href}
            className={cn(
                'mt-2 inline-flex items-center gap-1 text-[11px] font-medium text-brand transition hover:text-brand-hover',
                className,
            )}
        >
            {children}
            <ArrowRight className="h-3 w-3" />
        </Link>
    );
}
