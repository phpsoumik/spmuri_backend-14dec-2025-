<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CompanySettingsController extends Controller
{
    public function getCompanySettings(): JsonResponse
    {
        try {
            $settings = AppSetting::first();
            
            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'company_name' => $settings->companyName ?? '',
                    'company_gst_number' => $settings->company_gst_number ?? '',
                    'address' => $settings->address ?? '',
                    'phone' => $settings->phone ?? '',
                    'email' => $settings->email ?? '',
                ]
            ], 200);
            
        } catch (Exception $err) {
            return response()->json([
                'success' => false,
                'message' => $err->getMessage()
            ], 500);
        }
    }
    
    public function updateCompanySettings(Request $request): JsonResponse
    {
        try {
            $validate = validator($request->all(), [
                'company_name' => 'nullable|string|max:255',
                'company_gst_number' => 'nullable|string|max:15',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors()->first()
                ], 400);
            }
            
            $settings = AppSetting::first();
            
            $data = $request->all();
            // Map company_name to companyName for database
            if (isset($data['company_name'])) {
                $data['companyName'] = $data['company_name'];
                unset($data['company_name']);
            }
            
            if (!$settings) {
                $settings = AppSetting::create($data);
            } else {
                $settings->update($data);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Company settings updated successfully',
                'data' => $settings
            ], 200);
            
        } catch (Exception $err) {
            return response()->json([
                'success' => false,
                'message' => $err->getMessage()
            ], 500);
        }
    }
}