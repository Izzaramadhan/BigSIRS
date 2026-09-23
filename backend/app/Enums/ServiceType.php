<?php

namespace App\Enums;

enum ServiceType: string
{
    case RawatJalan     = 'rawat-jalan';
    case RawatInap      = 'rawat-inap';
    case Igd            = 'igd';
    case LayananPenunjang = 'layanan-penunjang';
    case Farmasi        = 'farmasi';
    case Billing        = 'billing';
    case Operasi        = 'operasi';
    case Icu            = 'icu';
    case Ponek          = 'ponek';
    case Vk             = 'vk';

    /**
     * Human-readable labels for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::RawatJalan       => 'Rawat Jalan',
            self::RawatInap        => 'Rawat Inap',
            self::Igd              => 'IGD',
            self::LayananPenunjang => 'Layanan Penunjang',
            self::Farmasi          => 'Farmasi',
            self::Billing          => 'Billing',
            self::Operasi          => 'Operasi',
            self::Icu              => 'ICU',
            self::Ponek            => 'PONEK',
            self::Vk               => 'VK',
        };
    }

    /**
     * Return all string values — useful for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
