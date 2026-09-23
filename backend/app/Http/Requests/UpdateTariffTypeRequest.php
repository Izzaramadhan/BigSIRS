<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTariffTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tariffType = $this->route('tariff_type');
        
        $codeRule = $tariffType->code !== null ? 'required' : 'nullable';

        return [
            'name' => 'required|string|max:255',
            'code' => [$codeRule, 'string', 'max:50', 'unique:tariff_types,code,' . $tariffType->id],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.id' => 'nullable|integer|exists:tariff_type_components,id',
            'components.*.tariff_component_id' => 'required|integer|exists:tariff_components,id',
            'components.*.percentage' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $components = $this->input('components', []);
            
            // Check for duplicate components in the input manually
            // We only enforce uniqueness for NEW components or if the user changed the component ID.
            // If they are legacy duplicates and they just update percentage, it's allowed.
            // Wait, actually, the user said:
            // "Untuk data legacy: Relasi duplikat harus dipertahankan terlebih dahulu... Pengguna boleh membersihkannya secara sadar, tetapi sistem tidak boleh menghapusnya otomatis."
            // So if they submit duplicate component IDs, and both have an existing component `id` from the pivot, we allow it.
            // But they cannot ADD a duplicate (where pivot `id` is null).
            // Let's implement this custom logic.
            
            $componentCounts = [];
            foreach ($components as $index => $comp) {
                $compId = $comp['tariff_component_id'] ?? null;
                $pivotId = $comp['id'] ?? null;
                
                if ($compId) {
                    if (!isset($componentCounts[$compId])) {
                        $componentCounts[$compId] = 0;
                    }
                    $componentCounts[$compId]++;
                    
                    if ($componentCounts[$compId] > 1 && !$pivotId) {
                        $validator->errors()->add("components.{$index}.tariff_component_id", 'Komponen ini tidak boleh ditambahkan lebih dari satu kali.');
                    }
                }
                
                // percentage validation for > 100
                $percentage = $comp['percentage'] ?? 0;
                if ($percentage > 100 && !$pivotId) {
                    $validator->errors()->add("components.{$index}.percentage", 'Persentase maksimal 100 untuk data baru.');
                }
                
                // If they update an existing pivot, and it had >100, they can keep it or must reduce it to <= 100?
                // "Jika nilai legacy outlier diubah, nilai baru wajib berada pada rentang 0–100."
                // Since we don't have the old value here easily, we might validate this in the controller or we can query it.
                // Let's just do standard validation up to 100 in the rules for new inputs, and handle legacy in the controller.
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->name ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
            'code' => $this->code ? strtoupper(trim($this->code)) : null,
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}
