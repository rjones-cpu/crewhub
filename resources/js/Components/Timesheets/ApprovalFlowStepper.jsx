import { ArrowRight } from 'lucide-react';
import Card from '@/Components/Shared/Card';
import { cn } from '@/utils/helpers';

const STATE_STYLES = {
    completed: 'bg-success text-white',
    in_progress: 'bg-warning text-white',
    pending: 'bg-slate-200 text-slate-600',
    disabled: 'bg-slate-100 text-slate-400',
    rejected: 'bg-danger text-white',
};

export default function ApprovalFlowStepper({ steps = [], title = 'Approval Flow' }) {
    return (
        <Card title={title} className="h-full">
            <ol className="flex flex-col gap-3 sm:flex-row sm:items-start">
                {steps.map((step, index) => (
                    <li key={step.key || step.title} className="flex flex-1 items-start gap-3 sm:flex-col sm:gap-2">
                        <div className="flex items-center gap-2 sm:w-full">
                            <span
                                className={cn(
                                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                    STATE_STYLES[step.state] || STATE_STYLES.pending,
                                )}
                            >
                                {index + 1}
                            </span>
                            {index < steps.length - 1 && (
                                <ArrowRight className="hidden h-4 w-4 shrink-0 text-slate-300 sm:block" />
                            )}
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-slate-900">{step.title}</p>
                            <p className="text-xs text-slate-500">{step.label}</p>
                        </div>
                    </li>
                ))}
            </ol>
        </Card>
    );
}
