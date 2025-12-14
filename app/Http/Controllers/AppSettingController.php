<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppSettingController extends Controller
{
    //get single app setting
    public function getSingleAppSetting(): JsonResponse
    {
        try {
            // Use correct table name - lowercase
            $getSingleAppSetting = \DB::table('appsetting')->where('id', 1)->first();
            
            if (!$getSingleAppSetting) {
                // Return default data if not found
                return response()->json([
                    'id' => 1,
                    'companyName' => 'SPMURI',
                    'address' => 'Dhaka, Bangladesh',
                    'phone' => '01700000000',
                    'email' => 'info@spmuri.com',
                    'website' => 'https://spmuri.dmscloud.in'
                ], 200);
            }

            // Convert to array and return
            $data = (array) $getSingleAppSetting;
            return response()->json($data, 200);
        } catch (Exception $error) {
            // Return fallback data on any error
            return response()->json([
                'id' => 1,
                'companyName' => 'SPMURI',
                'address' => 'Dhaka, Bangladesh',
                'phone' => '01700000000',
                'email' => 'info@spmuri.com',
                'website' => 'https://spmuri.dmscloud.in'
            ], 200);
        }
    }

    //update app setting
    public function updateAppSetting(Request $request): JsonResponse
    {
        try {
            $appSetting = AppSetting::where('id', 1)->first();
            
            //if logo is not empty then update the logo file. if is empty then update other fields but not replace the logo file.
            if ($request->hasFile('images')) {
                $file_paths = $request->file_paths;
                $appSetting->update([
                    'companyName' => $request->companyName ?? $appSetting->companyName,
                    'dashboardType' => $request->dashboardType ?? $appSetting->dashboardType,
                    'tagLine' => $request->tagLine ?? $appSetting->tagLine,
                    'address' => $request->address ?? $appSetting->address,
                    'phone' => $request->phone ?? $appSetting->phone,
                    'email' => $request->email  ?? $appSetting->email,
                    'website' => $request->website ?? $appSetting->website,
                    'company_gst_number' => $request->company_gst_number ?? $appSetting->company_gst_number,
                    'footer' => $request->footer   ?? $appSetting->footer,
                    'currencyId' => (int)$request->currencyId ?? $appSetting->currencyId,
                    'logo' => isset($file_paths[0]) ? $file_paths[0] : $appSetting->logo,
                ]);
                $converted = arrayKeysToCamelCase($appSetting->toArray());
                return response()->json(['message' => 'success', 'data' => $converted], 200);
            }

            $appSetting->update([
                'companyName' => $request->companyName ?? $appSetting->companyName,
                'dashboardType' => $request->dashboardType ?? $appSetting->dashboardType,
                'tagLine' => $request->tagLine ?? $appSetting->tagLine,
                'address' => $request->address ?? $appSetting->address,
                'phone' => $request->phone ?? $appSetting->phone,
                'email' => $request->email ?? $appSetting->email,
                'website' => $request->website ?? $appSetting->website,
                'company_gst_number' => $request->company_gst_number ?? $appSetting->company_gst_number,
                'footer' => $request->footer ?? $appSetting->footer,
                'currencyId' => (int)$request->currencyId ?? $appSetting->currencyId,
            ]);
            $converted = arrayKeysToCamelCase($appSetting->toArray());
            return response()->json(['message' => 'success', 'data' => $converted], 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during updating app setting. Please try again later.'], 500);
        }
    }
}
