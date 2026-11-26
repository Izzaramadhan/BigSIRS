<?php

namespace App\Support;

use App\Enums\GuarantorType;
use App\Models\Guarantor;
use App\Models\Legacy\LegacyGuarantor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class GuarantorValidation
{
    /**
     * Validasi ID penjamin terhadap Master Data (legacy atau internal).
     * Mengizinkan ID historis yang tidak diubah pada operasi pembaruan data.
     */
    public static function validateGuarantorId(Validator $validator, mixed $guarantorId, ?int $currentGuarantorId = null): void
    {
        if ($guarantorId === null || $guarantorId === '') {
            return;
        }

        $targetId = (int) $guarantorId;
        $isHistoricalUnchanged = $currentGuarantorId !== null && $targetId === $currentGuarantorId;

        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $row = DB::connection('legacy')
                ->table('ref_jenis_asuransi')
                ->where('id', $targetId)
                ->first();

            if (! $row) {
                $validator->errors()->add('guarantor_id', 'Pilihan jenis jaminan tidak valid atau sudah nonaktif.');
                return;
            }

            if ($isHistoricalUnchanged) {
                return;
            }

            $isDeleted = ! empty($row->deleted_at) && $row->deleted_at !== '0000-00-00 00:00:00';
            $isActive = (string) ($row->status ?? '1') === '1';

            if ($isDeleted || ! $isActive) {
                $validator->errors()->add('guarantor_id', 'Pilihan jenis jaminan tidak valid atau sudah nonaktif.');
            }

            return;
        }

        if (Schema::hasTable('guarantors')) {
            $guarantor = Guarantor::withTrashed()->find($targetId);

            if (! $guarantor) {
                $validator->errors()->add('guarantor_id', 'Pilihan jenis jaminan tidak valid atau sudah nonaktif.');
                return;
            }

            if ($isHistoricalUnchanged) {
                return;
            }

            if ($guarantor->trashed() || ! $guarantor->is_active) {
                $validator->errors()->add('guarantor_id', 'Pilihan jenis jaminan tidak valid atau sudah nonaktif.');
            }
        }
    }

    /**
     * Tentukan apakah ID penjamin bertipe bayar mandiri (SelfPay / UMUM).
     */
    public static function isSelfPay(int $guarantorId): bool
    {
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $guarantor = LegacyGuarantor::withTrashed()->find($guarantorId);
            if ($guarantor) {
                return $guarantor->type === GuarantorType::SelfPay;
            }
        } elseif (Schema::hasTable('guarantors')) {
            $guarantor = Guarantor::withTrashed()->find($guarantorId);
            if ($guarantor) {
                return $guarantor->type === GuarantorType::SelfPay;
            }
        }

        return $guarantorId === 2;
    }

    /**
     * Dapatkan ID penjamin UMUM/SelfPay default dari Master Data bila tersedia.
     */
    public static function resolveDefaultSelfPayId(): int
    {
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $id = DB::connection('legacy')
                ->table('ref_jenis_asuransi')
                ->where(function ($q) {
                    $q->whereNull('deleted_at')->orWhere('deleted_at', '0000-00-00 00:00:00');
                })
                ->where('status', '1')
                ->where(function ($q) {
                    $q->whereRaw("UPPER(COALESCE(type, '')) IN ('UMUM', 'SELF_PAY')")
                        ->orWhereRaw("UPPER(jenis_asuransi) LIKE '%UMUM%'");
                })
                ->orderBy('id')
                ->value('id');

            if ($id) {
                return (int) $id;
            }
        } elseif (Schema::hasTable('guarantors')) {
            $id = Guarantor::query()
                ->where('is_active', true)
                ->where('type', GuarantorType::SelfPay->value)
                ->orderBy('id')
                ->value('id');

            if ($id) {
                return (int) $id;
            }
        }

        return 2;
    }
}
