import { APPROVAL_STATES } from './ApprovalState';

const LEGEND_STATES = ['approved', 'pending', 'returned', 'confirmed', 'not_required', 'overdue'];

export default function StatusLegend() {
    return (
        <div className="card rounded-lg p-3">
            <h2 className="mb-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-700">
                Status Legend
            </h2>
            <ul className="flex flex-wrap gap-x-5 gap-y-1">
                {LEGEND_STATES.map((state) => (
                    <li key={state} className="flex items-center gap-1.5 text-[8px] text-slate-600">
                        <span className={`h-1.5 w-1.5 rounded-full ${APPROVAL_STATES[state].dot}`} />
                        {APPROVAL_STATES[state].label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
