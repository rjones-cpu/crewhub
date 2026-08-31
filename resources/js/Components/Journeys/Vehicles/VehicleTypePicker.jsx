import { Bike, Bus, Car, CarFront, CircleCheck, Tractor, Truck } from 'lucide-react';
import { cn } from '@/utils/helpers';

const TYPE_ICONS = {
    atv_utv: Bike,
    passenger_car: Car,
    suv: CarFront,
    truck: Truck,
    bus: Bus,
    utility_vehicle: Tractor,
};

export default function VehicleTypePicker({ options = [], value, onChange, error }) {
    return (
        <div>
            <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                {options.map((option) => {
                    const Icon = TYPE_ICONS[option.value] || Car;
                    const selected = value === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => onChange(option.value)}
                            aria-pressed={selected}
                            className={cn(
                                'relative flex flex-col items-center gap-2 rounded-lg border px-2 py-3 transition',
                                selected
                                    ? 'border-brand bg-brand-soft/40 ring-1 ring-brand'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50',
                            )}
                        >
                            {selected && (
                                <CircleCheck
                                    className="absolute right-1.5 top-1.5 h-3.5 w-3.5 text-brand"
                                    strokeWidth={2}
                                />
                            )}
                            <Icon
                                className={cn('h-7 w-7', selected ? 'text-brand' : 'text-slate-700')}
                                strokeWidth={1.6}
                            />
                            <span
                                className={cn(
                                    'text-center text-[11px] font-medium leading-tight',
                                    selected ? 'text-brand' : 'text-slate-700',
                                )}
                            >
                                {option.label}
                            </span>
                        </button>
                    );
                })}
            </div>
            {error && <p className="mt-1.5 text-[11px] text-danger">{error}</p>}
        </div>
    );
}
