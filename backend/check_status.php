<?php

use App\Models\MasterData\Doctor;

$total = Doctor::count();
$active = Doctor::where('is_active', true)->count();
$inactive = Doctor::where('is_active', false)->count();
$sample = Doctor::select('id', 'is_active')->limit(3)->get();
echo json_encode([
    'total' => $total,
    'active' => $active,
    'inactive' => $inactive,
    'sample' => $sample,
]);
