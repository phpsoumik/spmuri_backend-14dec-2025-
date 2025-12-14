<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class B2BCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = B2BCompany::with(['parent', 'subCompanies']);
            
            if ($request->has('type')) {
                if ($request->type === 'main') {
                    $query->mainCompanies();
                } elseif ($request->type === 'independent') {
                    $query->independentCompanies();
                } elseif ($request->type === 'sub') {
                    $query->subCompanies();
                }
            }
            
            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }
            
            $companies = $query->orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'parent_id' => 'nullable|exists:b2b_companies,id',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'gst_number' => 'nullable|string|max:15',
                'state' => 'nullable|string|max:100',
                'state_code' => 'nullable|string|max:2',
                'pin_code' => 'nullable|string|max:10',
                'hsn_code' => 'nullable|string|max:20',
                'gst_rate' => 'nullable|numeric|min:0|max:100',
                'vehicle_number' => 'nullable|string|max:20',
                'sub_companies' => 'nullable|array',
                'sub_companies.*.name' => 'nullable|string|max:255',
                'sub_companies.*.contact_person' => 'nullable|string|max:255',
                'sub_companies.*.phone' => 'nullable|string|max:20',
                'sub_companies.*.address' => 'nullable|string',
                'sub_companies.*.gst_number' => 'nullable|string|max:15'
            ]);
            
            // Create main company
            $mainCompanyData = collect($validated)->except('sub_companies')->toArray();
            $company = B2BCompany::create($mainCompanyData);
            
            // Create sub companies if provided
            if (isset($validated['sub_companies']) && is_array($validated['sub_companies'])) {
                foreach ($validated['sub_companies'] as $subCompanyData) {
                    if (!empty($subCompanyData['name'])) {
                        B2BCompany::create([
                            'name' => $subCompanyData['name'],
                            'parent_id' => $company->id,
                            'contact_person' => $subCompanyData['contact_person'] ?? null,
                            'phone' => $subCompanyData['phone'] ?? null,
                            'address' => $subCompanyData['address'] ?? null,
                            'gst_number' => $subCompanyData['gst_number'] ?? null,
                            'state' => $validated['state'] ?? null,
                            'state_code' => $validated['state_code'] ?? null
                        ]);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Company created successfully',
                'data' => $company->load(['parent', 'subCompanies'])
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $company = B2BCompany::with(['parent', 'subCompanies'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $company
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $company = B2BCompany::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'parent_id' => 'nullable|exists:b2b_companies,id',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'gst_number' => 'nullable|string|max:15',
                'state' => 'nullable|string|max:100',
                'state_code' => 'nullable|string|max:2',
                'pin_code' => 'nullable|string|max:10',
                'hsn_code' => 'nullable|string|max:20',
                'gst_rate' => 'nullable|numeric|min:0|max:100',
                'vehicle_number' => 'nullable|string|max:20',
                'is_active' => 'boolean'
            ]);
            
            $company->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company->load(['parent', 'subCompanies'])
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $company = B2BCompany::findOrFail($id);
            
            // Check if company has sub-companies
            if ($company->subCompanies()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete company with sub-companies'
                ], 400);
            }
            
            $company->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addSubCompany(Request $request, $parentId): JsonResponse
    {
        try {
            $parent = B2BCompany::findOrFail($parentId);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'gst_number' => 'nullable|string|max:15',
                'state' => 'nullable|string|max:100',
                'state_code' => 'nullable|string|max:2',
                'pin_code' => 'nullable|string|max:10',
                'hsn_code' => 'nullable|string|max:20',
                'gst_rate' => 'nullable|numeric|min:0|max:100',
                'vehicle_number' => 'nullable|string|max:20'
            ]);
            
            $validated['parent_id'] = $parentId;
            $validated['type'] = 'main'; // Sub-companies are also type 'main'
            
            $subCompany = B2BCompany::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Sub-company created successfully',
                'data' => $subCompany->load('parent')
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}