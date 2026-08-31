import { BedDouble } from 'lucide-react';
import Modal from '@/Components/Shared/Modal';

export default function RoomNightsModal({ dates = [], selected = [], onToggle, onConfirm, onClose }) {
    return (
        <Modal show onClose={onClose} title="Room nights needed" maxWidth="md">
            <p className="mb-3 text-xs text-slate-500">
                Three or more travel days were assigned. Choose which nights need a lodge bed — the same
                room-night step camp.site uses when painting yellow days.
            </p>
            <div className="flex flex-wrap gap-1.5">
                {dates.map((date) => {
                    const active = selected.includes(date);

                    return (
                        <button
                            key={date}
                            type="button"
                            onClick={() => onToggle(date)}
                            className={
                                active
                                    ? 'rounded border border-brand bg-brand px-2 py-1 text-[11px] font-medium text-white'
                                    : 'rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 hover:border-slate-300'
                            }
                        >
                            {date}
                        </button>
                    );
                })}
            </div>
            <div className="mt-4 flex justify-end gap-2">
                <button type="button" onClick={onClose} className="btn-secondary min-h-8 px-2.5 py-1.5 text-xs">
                    Skip
                </button>
                <button type="button" onClick={onConfirm} className="btn-primary min-h-8 px-2.5 py-1.5 text-xs">
                    <BedDouble className="h-3.5 w-3.5" />
                    Save room nights
                </button>
            </div>
        </Modal>
    );
}
