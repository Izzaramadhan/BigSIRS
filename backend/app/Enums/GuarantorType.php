<?php

namespace App\Enums;

enum GuarantorType: string
{
    case SelfPay = 'self_pay';
    case Bpjs = 'bpjs';
    case Government = 'government';
    case PrivateInsurance = 'private_insurance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SelfPay => 'Umum/Tunai',
            self::Bpjs => 'BPJS',
            self::Government => 'Program Pemerintah',
            self::PrivateInsurance => 'Asuransi Swasta',
            self::Other => 'Lainnya',
        };
    }
}
