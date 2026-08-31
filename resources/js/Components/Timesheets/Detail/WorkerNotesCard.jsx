export default function WorkerNotesCard({ notes, editable, onChange }) {
    return (
        <div className="card card-padding h-full">
            <h2 className="text-[10px] font-bold uppercase tracking-wider text-slate-700">
                Worker Notes
            </h2>

            {editable ? (
                <textarea
                    rows={3}
                    className="input-field mt-3 text-xs"
                    placeholder="Add any notes about the work completed this period."
                    value={notes ?? ''}
                    onChange={(event) => onChange(event.target.value)}
                />
            ) : (
                <p className="mt-3 whitespace-pre-line text-xs leading-relaxed text-slate-600">
                    {notes || 'No notes recorded for this period.'}
                </p>
            )}
        </div>
    );
}
