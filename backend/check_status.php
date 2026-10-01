<?php
$total = \App\Models\MasterData\Doctor::count();
$active = \App\Models\MasterData\Doctor::where('is_active', true)->count();
$inactive = \App\Models\MasterData\Doctor::where('is_active', false)->count();
$sample = \App\Models\MasterData\Doctor::select('id', 'is_active')->limit(3)->get();
echo json_encode([
    'total' => $total, 
    'active' => $active, 
    'inactive' => $inactive, 
    'sample' => $sample
]);
