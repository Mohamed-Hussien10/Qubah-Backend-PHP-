<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Package::with(['educationalStage', 'grade', 'section', 'subject']);

        if ($request->has('search') && !empty($request->query('search'))) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->has('educational_stage_id')) {
            $query->where('educational_stage_id', $request->query('educational_stage_id'));
        }

        $packages = $query->latest()->get();

        return response()->json([
            'data' => $packages,
            'success' => true,
        ]);
    }

    /**
     * Display the specified package.
     */
    public function show($id): JsonResponse
    {
        $package = Package::with(['educationalStage', 'grade', 'section', 'subject'])->find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found', 'success' => false], 404);
        }

        return response()->json([
            'data' => $package,
            'success' => true,
        ]);
    }

    /**
     * Store a newly created package.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'educational_stage_id' => 'required|exists:educational_stages,id',
            'grade_id' => 'nullable|exists:grades,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'description' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $package = Package::create($validated);
        $package->load(['educationalStage', 'grade', 'section', 'subject']);

        return response()->json([
            'message' => 'Package created successfully',
            'data' => $package,
            'success' => true,
        ], 201);
    }

    /**
     * Update the specified package.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $package = Package::find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found', 'success' => false], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'educational_stage_id' => 'sometimes|required|exists:educational_stages,id',
            'grade_id' => 'nullable|exists:grades,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'description' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $package->update($validated);
        $package->load(['educationalStage', 'grade', 'section', 'subject']);

        return response()->json([
            'message' => 'Package updated successfully',
            'data' => $package,
            'success' => true,
        ]);
    }

    /**
     * Remove the specified package.
     */
    public function destroy($id): JsonResponse
    {
        $package = Package::find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found', 'success' => false], 404);
        }

        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully',
            'success' => true,
        ]);
    }
}
