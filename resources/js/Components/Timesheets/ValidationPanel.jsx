import { CheckCircle2, AlertCircle } from 'lucide-react';
import Card from '@/Components/Shared/Card';

function missingCount(entries = [], equipment = [], compliance = {}) {
    let missing = 0;

    entries.forEach((row) => {
        if (row.shift && row.shift !== 'Off') {
            if (!row.work_location) missing += 1;
            if (!row.task) missing += 1;
            if (!row.start_time || !row.end_time) missing += 1;
        }
    });

    equipment.forEach((row) => {
        if (!row.equipment_type || !row.unit_id) missing += 1;
    });

    if (!compliance.signature) missing += 1;
    if (!compliance.worker_declaration) missing += 1;

    return missing;
}

export default function ValidationPanel({ entries, equipment, compliance }) {
    const missing = missingCount(entries, equipment, compliance);
    const complete = missing === 0;

    return (
        <Card title="Validation">
            <div
                className={`flex items-start gap-3 rounded-lg border px-3 py-3 ${
                    complete ? 'border-success/20 bg-success-soft' : 'border-warning/20 bg-warning-soft'
                }`}
            >
                {complete ? (
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-success" />
                ) : (
                    <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-warning" />
                )}
                <div>
                    <p className="text-sm font-medium text-slate-900">
                        {complete ? '0 — All required fields complete' : `${missing} missing field(s)`}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {complete
                            ? 'Ready for submission when compliance items are checked.'
                            : 'Complete required time, equipment, and compliance fields.'}
                    </p>
                </div>
            </div>
        </Card>
    );
}
