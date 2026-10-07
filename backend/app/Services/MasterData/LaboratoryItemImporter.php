<?php

namespace App\Services\MasterData;

use App\Models\MasterData\LaboratoryItem;

class LaboratoryItemImporter
{
    public function __construct(private LegacyLaboratoryItemSource $source) {}

    /**
     * Import Item Lab from the legacy ref_item_lab table (idempotent by legacy_id).
     *
     * @return array<string, int>
     */
    public function import(bool $dryRun = false): array
    {
        $stats = [
            'total' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $this->source->chunk(function ($rows) use (&$stats, $dryRun) {
            foreach ($rows as $row) {
                $stats['total']++;

                try {
                    $name = trim((string) $row->nama);
                    if ($name === '') {
                        $stats['skipped']++;

                        continue;
                    }

                    $attributes = [
                        'name' => $name,
                        'reference_value' => $this->nullableText($row->standar_normal),
                        'unit' => $this->nullableText($row->satuan),
                        'is_active' => (string) $row->status === '1',
                    ];

                    $item = LaboratoryItem::withTrashed()->where('legacy_id', $row->id)->first();

                    if (! $item) {
                        if (! $dryRun) {
                            LaboratoryItem::create(['legacy_id' => $row->id] + $attributes);
                        }
                        $stats['inserted']++;

                        continue;
                    }

                    $isDirty = $item->trashed() || $item->only(array_keys($attributes)) !== $attributes;

                    if ($isDirty) {
                        if (! $dryRun) {
                            if ($item->trashed()) {
                                $item->restore();
                            }
                            $item->update($attributes);
                        }
                        $stats['updated']++;
                    } else {
                        $stats['unchanged']++;
                    }
                } catch (\Throwable) {
                    $stats['failed']++;
                }
            }
        });

        return $stats;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
