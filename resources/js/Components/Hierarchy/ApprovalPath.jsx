import { Link } from '@inertiajs/react';
import { ArrowRight, Info } from 'lucide-react';
import Card from '@/Components/Shared/Card';

export default function ApprovalPath({ steps = [] }) {
    return (
        <Card
            className="h-full"
            title="Timesheet Approval Path"
            actions={
                <Link
                    href={route('timesheets.index')}
                    className="text-sm font-medium text-brand hover:text-brand-hover"
                >
                    View details
                </Link>
            }
        >
            <ol className="flex flex-col gap-3 sm:flex-row sm:items-start">
                {steps.map((step, index) => (
                    <li key={step.step} className="flex flex-1 items-start gap-3 sm:flex-col sm:gap-2">
                        <div className="flex items-center gap-2 sm:w-full">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-semibold text-white">
                                {step.step}
                            </span>
                            {index < steps.length - 1 && (
                                <ArrowRight className="hidden h-4 w-4 shrink-0 text-slate-300 sm:block" />
                            )}
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-slate-900">{step.title}</p>
                            <p className="text-xs text-slate-500">{step.subtitle}</p>
                        </div>
                    </li>
                ))}
            </ol>

            <p className="mt-4 flex items-start gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                Timesheets are reviewed by the connected manager(s) for this project.
            </p>
        </Card>
    );
}
