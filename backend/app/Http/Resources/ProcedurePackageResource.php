<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedurePackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'total_amount' => $this->total_amount,
            'is_active' => $this->is_active,
            'items' => $this->whenLoaded('items', function() {
                return $this->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'medical_procedure_id' => $item->medical_procedure_id,
                        'medical_procedure_tariff_id' => $item->medical_procedure_tariff_id,
                        'quantity' => $item->quantity,
                        'unit_amount' => $item->unit_amount,
                        'subtotal_amount' => $item->subtotal_amount,
                        'sort_order' => $item->sort_order,
                        'procedure' => $item->relationLoaded('procedure') && $item->procedure ? [
                            'id' => $item->procedure->id,
                            'code' => $item->procedure->code,
                            'name' => $item->procedure->name,
                            'is_visible' => $item->procedure->is_visible,
                        ] : null,
                        'tariff' => $item->relationLoaded('tariff') && $item->tariff ? [
                            'id' => $item->tariff->id,
                            'tariff_type_id' => $item->tariff->tariff_type_id,
                            'tariff_type' => $item->tariff->relationLoaded('tariffType') ? [
                                'id' => $item->tariff->tariffType->id,
                                'name' => $item->tariff->tariffType->name,
                            ] : null
                        ] : null
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
