import { Info, Link2 } from 'lucide-react';

export default function ConnectionSummary({ project, company }) {
    return (
        <div className="card card-padding">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="flex items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand">
                        <Link2 className="h-5 w-5" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Connected Major Project
                        </p>
                        <p className="truncate text-base font-semibold text-brand">
                            {project?.name || 'No project connected'}
                        </p>
                        <p className="truncate text-sm text-slate-500">{company?.name}</p>
                    </div>
                </div>

                <div className="lg:border-l lg:border-slate-100 lg:pl-6">
                    <p className="text-sm font-semibold text-slate-900">How this works</p>
                    <p className="mt-1 text-sm text-slate-500">
                        This Crew Hub is connected to the major project above and reports to the
                        manager(s) listed. Add managers using the + control. The workforce reports
                        upward through the connected manager(s).
                    </p>
                </div>

                <div className="flex items-start gap-2 lg:border-l lg:border-slate-100 lg:pl-6">
                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                    <p className="text-sm text-slate-500">
                        Each connected major project shows only the manager(s) this Crew Hub
                        reports to.
                    </p>
                </div>
            </div>
        </div>
    );
}
