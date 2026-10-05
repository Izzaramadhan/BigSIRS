<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalProcedureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_visible' => $this->is_visible,
            'needs_review' => $this->needs_review,
            'procedure_category_id' => $this->procedure_category_id,
            'icd9_cm_id' => $this->icd9_cm_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => new ProcedureCategoryResource($this->whenLoaded('procedureCategory')),
            'icd9_cm' => $this->whenLoaded('icd9Cm'), // We can use simple array cast for ICD9 since it's simple
            'polyclinics' => PolyclinicResource::collection($this->whenLoaded('polyclinics')),
            'report_groups' => $this->whenLoaded('reportGroups'),
            'tariffs' => $this->whenLoaded('tariffs', function () {
                return $this->tariffs->map(function ($tariff) {
                    return [
                        'id' => $tariff->id,
                        'tariff_type_id' => $tariff->tariff_type_id,
                        'total_amount' => $tariff->total_amount,
                        'tariff_type' => $tariff->relationLoaded('tariffType') ? [
                            'id' => $tariff->tariffType->id,
                            'name' => $tariff->tariffType->name,
                            'code' => $tariff->tariffType->code,
                        ] : null,
                        'components' => $tariff->relationLoaded('components') ? $tariff->components->map(function ($comp) {
                            return [
                                'id' => $comp->id,
                                'tariff_component_id' => $comp->tariff_component_id,
                                'percentage_snapshot' => $comp->percentage_snapshot,
                                'amount' => (float) $comp->amount,
                                'tariff_component' => $comp->relationLoaded('tariffComponent') ? [
                                    'id' => $comp->tariffComponent->id,
                                    'name' => $comp->tariffComponent->name,
                                ] : null,
                            ];
                        }) : [],
                    ];
                });
            }),
        ];
    }
}
