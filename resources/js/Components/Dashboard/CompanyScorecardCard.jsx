import { Info } from 'lucide-react';
import CardFooterLink from './CardFooterLink';
import GradeBadge from './GradeBadge';

export default function CompanyScorecardCard({ scorecard = {}, href }) {
    const criteria = scorecard.criteria || [];

    return (
        <div className="card flex h-full flex-col p-4">
            <div className="flex items-center gap-1.5">
                <h3 className="section-title">Company Performance Scorecard</h3>
                <Info className="h-3 w-3 text-slate-400" />
            </div>

            <div className="mt-3 flex flex-1 gap-4">
                <div className="flex w-[38%] shrink-0 flex-col items-center justify-center text-center">
                    <GradeBadge grade={scorecard.grade} size="lg" />
                    <p className="mt-2 text-base font-bold leading-none text-slate-900">
                        {scorecard.label}
                    </p>
                    <p className="mt-1 text-[10px] text-slate-500">{scorecard.status}</p>
                </div>

                <ul className="min-w-0 flex-1 divide-y divide-slate-100 self-center">
                    {criteria.map((criterion, index) => (
                        <li
                            key={criterion.name}
                            className="flex items-center justify-between gap-2 py-1.5"
                        >
                            <span className="min-w-0 truncate text-[10px] font-medium text-slate-700">
                                {index + 1}. {criterion.name}
                            </span>
                            <span className="flex shrink-0 items-center gap-2">
                                <span className="text-[9px] text-slate-500">{criterion.detail}</span>
                                <GradeBadge grade={criterion.grade} variant="text" className="text-[11px]" />
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <p className="mt-2 text-[10px] text-slate-500">
                Next Review: {scorecard.next_review}
            </p>

            <CardFooterLink href={href}>View Scorecard Details</CardFooterLink>
        </div>
    );
}
