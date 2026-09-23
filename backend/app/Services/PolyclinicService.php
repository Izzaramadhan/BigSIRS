<?php

namespace App\Services;

use App\Models\Polyclinic;

class PolyclinicService
{
    public function detectCycle(int $polyclinicId, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($polyclinicId === $newParentId) {
            return true;
        }

        $currentParentId = $newParentId;
        
        while ($currentParentId !== null) {
            if ($currentParentId === $polyclinicId) {
                return true; // Cycle detected
            }
            
            $parent = Polyclinic::find($currentParentId);
            
            if (!$parent) {
                break;
            }
            
            $currentParentId = $parent->parent_id;
        }

        return false;
    }
}
