<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = (float) ($this->price ?? 0);
        $formattedPrice = 'Rp' . number_format($price, 0, ',', '.');

        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'code' => $this->code,
            'name' => $this->name,
            'price' => $price,
            'formatted_price' => $formattedPrice,
            'description' => $this->description,
            'aplicare_code' => $this->aplicare_code,
            'sirs_code' => $this->sirs_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
