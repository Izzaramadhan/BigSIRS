<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedurePackageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'total_amount' => $this->total_amount,
            'is_active' => $this->is_active,
            'items_count' => $this->whenCounted('items'),
            'items_summary' => $this->whenLoaded('items', function() {
                return $this->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'procedure_name' => $item->procedure->name ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'subtotal_amount' => $item->subtotal_amount,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
