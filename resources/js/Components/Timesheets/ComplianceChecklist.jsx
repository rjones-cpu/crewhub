import Card from '@/Components/Shared/Card';

const ITEMS = [
    { key: 'safety_meeting', label: 'Safety Meeting' },
    { key: 'toolbox_talk', label: 'Toolbox Talk' },
    { key: 'incident_report', label: 'Incident Report' },
    { key: 'attachments', label: 'Attachment Uploads' },
    { key: 'signature', label: 'Signature' },
    { key: 'worker_declaration', label: 'Worker Declaration' },
];

export default function ComplianceChecklist({ compliance = {}, editable, onChange }) {
    const toggle = (key) => {
        if (!editable) {
            return;
        }

        onChange?.({
            ...compliance,
            [key]: !compliance[key],
        });
    };

    return (
        <Card title="Compliance Checklist">
            <ul className="space-y-2.5">
                {ITEMS.map((item) => (
                    <li key={item.key} className="flex items-center justify-between gap-3">
                        <label className="flex items-center gap-2.5 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                className="rounded border-slate-300 text-brand focus:ring-brand"
                                checked={Boolean(compliance[item.key])}
                                disabled={!editable}
                                onChange={() => toggle(item.key)}
                            />
                            {item.label}
                        </label>
                        {editable && (
                            <button type="button" className="text-xs font-medium text-brand hover:underline">
                                Add details
                            </button>
                        )}
                    </li>
                ))}
            </ul>
        </Card>
    );
}
