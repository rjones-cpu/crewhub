<?php

namespace App\Enums;

enum VehicleType: string
{
    case AtvUtv = 'atv_utv';
    case PassengerCar = 'passenger_car';
    case Suv = 'suv';
    case Truck = 'truck';
    case Bus = 'bus';
    case UtilityVehicle = 'utility_vehicle';

    public function label(): string
    {
        return match ($this) {
            self::AtvUtv => 'ATV / UTV',
            self::PassengerCar => 'Passenger Car',
            self::Suv => 'SUV',
            self::Truck => 'Truck',
            self::Bus => 'Bus',
            self::UtilityVehicle => 'Utility Vehicle',
        };
    }

    /**
     * Risk points contributed by the vehicle class alone, before route conditions.
     * Heavier and open vehicles carry more exposure on mine haul roads.
     */
    public function riskPoints(): int
    {
        return match ($this) {
            self::AtvUtv => 20,
            self::PassengerCar => 12,
            self::Suv => 6,
            self::Truck => 14,
            self::Bus => 16,
            self::UtilityVehicle => 10,
        };
    }
}
