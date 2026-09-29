<?php

namespace App\Http\Resources\Api\V1\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Icd10CodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'english_name' => $this->english_name,
            'description' => $this->description,
            'is_medical_history' => (bool)$this->is_medical_history,
            'is_active' => (bool)$this->is_active,
            'inacbg_code' => $this->inacbg_code,
            'inacbg_name' => $this->inacbg_name,
            'class_1_tariff' => (float)$this->class_1_tariff,
            'class_2_tariff' => (float)$this->class_2_tariff,
            'class_3_tariff' => (float)$this->class_3_tariff,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
