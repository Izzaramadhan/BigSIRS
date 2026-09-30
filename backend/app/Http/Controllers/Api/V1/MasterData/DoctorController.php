<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDoctorRequest;
use App\Http\Requests\MasterData\UpdateDoctorRequest;
use App\Http\Resources\MasterData\DoctorResource;
use App\Http\Resources\MasterData\DoctorLookupResource;
use App\Models\MasterData\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::with(['employee', 'specialization']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%"); // legacy_id or code might be NIK
            });
        }

        if ($request->has('specialization_id')) {
            $query->where('specialization_id', $request->specialization_id);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);
        $direction = $sortDesc ? 'desc' : 'asc';
        
        // Allowed sort columns whitelist
        $allowedSorts = ['id', 'created_at', 'is_active', 'sip_number', 'str_number'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        }

        $perPage = $request->get('per_page', 10);
        $doctors = $query->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    public function store(StoreDoctorRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            
            // Handle signature upload
            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature_path'] = $path;
            }

            $doctor = Doctor::create($data);
            
            return new DoctorResource($doctor->load(['employee', 'specialization']));
        });
    }

    public function show(Doctor $doctor)
    {
        return new DoctorResource($doctor->load(['employee', 'specialization']));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        return DB::transaction(function () use ($request, $doctor) {
            $data = $request->validated();

            if ($request->hasFile('signature')) {
                // Delete old signature
                if ($doctor->signature_path && Storage::disk('public')->exists($doctor->signature_path)) {
                    Storage::disk('public')->delete($doctor->signature_path);
                }
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature_path'] = $path;
            } elseif ($request->boolean('remove_signature')) {
                if ($doctor->signature_path && Storage::disk('public')->exists($doctor->signature_path)) {
                    Storage::disk('public')->delete($doctor->signature_path);
                }
                $data['signature_path'] = null;
            }

            $doctor->update($data);

            return new DoctorResource($doctor->load(['employee', 'specialization']));
        });
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return response()->noContent();
    }

    public function updateStatus(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $doctor->update(['is_active' => $validated['is_active']]);

        return new DoctorResource($doctor->load(['employee', 'specialization']));
    }

    public function lookup(Request $request)
    {
        $query = Doctor::with(['employee', 'specialization']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } else if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Always include specifically requested IDs even if inactive
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        $doctors = $query->paginate(20);

        return DoctorLookupResource::collection($doctors);
    }
}
