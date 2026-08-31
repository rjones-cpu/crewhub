import { CircleCheck, ShieldCheck } from 'lucide-react';
import { cn } from '@/utils/helpers';

export default function RegistrationChecklist({ items = [] }) {
    return (
        <aside className="w-full shrink-0 space-y-3 lg:sticky lg:top-24 lg:w-[260px]">
            <div className="card p-4">
                <h2 className="text-xs font-semibold text-slate-900">Vehicle Registration Checklist</h2>
                <p className="mt-0.5 text-[11px] text-slate-500">
                    Complete all required sections to register the vehicle.
                </p>

                <ul className="mt-3 space-y-3">
                    {items.map((item) => (
                        <li key={item.title} className="flex gap-2">
                            <CircleCheck
                                className={cn(
                                    'mt-0.5 h-4 w-4 shrink-0',
                                    item.complete ? 'text-success' : 'text-slate-300',
                                )}
                                strokeWidth={1.8}
                            />
                            <div className="min-w-0">
                                <p
                                    className={cn(
                                        'text-[11px] font-medium',
                                        item.complete ? 'text-slate-900' : 'text-slate-500',
                                    )}
                                >
                                    {item.title}
                                </p>
                                <p className="mt-0.5 text-[11px] leading-snug text-slate-400">
                                    {item.description}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="rounded-lg bg-brand-soft px-3 py-3">
                <div className="flex gap-2">
                    <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-brand" strokeWidth={1.8} />
                    <div>
                        <p className="text-[11px] font-semibold text-brand">
                            All data is secure and compliant.
                        </p>
                        <p className="mt-0.5 text-[11px] leading-snug text-brand/80">
                            Your information is encrypted and stored securely in accordance with company
                            policy.
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    );
}
