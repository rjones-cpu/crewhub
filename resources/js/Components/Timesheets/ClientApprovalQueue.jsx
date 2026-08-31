import { Info } from 'lucide-react';
import Card from '@/Components/Shared/Card';

export default function ClientApprovalQueue({ enabled = false }) {
    return (
        <Card title="Client Approval Queue" className="h-full">
            {enabled ? (
                <p className="text-sm text-slate-600">
                    Client approval is enabled for the selected project. Manager-approved timesheets
                    appear here for client review.
                </p>
            ) : (
                <div className="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50 px-4 py-5">
                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                    <p className="text-sm text-slate-600">
                        Client approval is not required. This stage is disabled for the selected major
                        project.
                    </p>
                </div>
            )}
        </Card>
    );
}
