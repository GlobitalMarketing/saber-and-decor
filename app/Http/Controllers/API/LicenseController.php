<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;


use App\Models\License;
use App\Enums\LicenseStatus;
use App\Models\Installation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{

    /**
     * validate-license for the plugin users
     *
     * @param $request
     * @return JsonResponse
     */
    public function validateLicense(Request $request)
    {
        // return $request;
        $request->validate([
            'license_key' => 'required|string',
            // 'site_url'    => 'required|url',
        ]);

        // Check if the license exists
        $license = License::where('license_key', $request->license_key)
                        // ->where('site_url', $request->site_url)
                        ->first();

        if ($license) {
            if($license->status == LicenseStatus::INACTIVE){
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'License key has been inactivated please contact the support at support@web2go.co.za',
                ], 400);
            }
            if($license->status == LicenseStatus::EXPIRED){
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'License key has been expired please contact the support at support@web2go.co.za',
                ], 400);
            }
            return response()->json([
                'status' => 'valid',
                'message' => 'License key is valid.',
            ], 200);
        }

        return response()->json([
            'status' => 'invalid',
            'message' => 'Invalid license key.',
        ], 400);
    }

    /**
     * sync-api-keys for the plugin users
     *
     * @param $request
     * @return JsonResponse
     */
    public function syncApiKeys(Request $request)
    {
        $request->validate([
            'license_key'     => 'required|string',
            'consumer_key'    => 'required|string',
            'consumer_secret' => 'required|string',
            // 'site_url'        => 'required|url',
        ]);

        // Validate the license first
        $license = License::where('license_key', $request->license_key)
                        // ->where('site_url', $request->site_url)
                        ->first();

        if (!$license) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid license key. Cannot sync API keys.',
            ], 400);
        }

        if($license->status === LicenseStatus::INACTIVE){
            return response()->json([
                'status' => 'invalid',
                'message' => 'License key has been inactivated please contact the support at support@web2go.co.za',
            ], 400);
        }
        if($license->status === LicenseStatus::EXPIRED){
            return response()->json([
                'status' => 'invalid',
                'message' => 'License key has been expired please contact the support at support@web2go.co.za',
            ], 400);
        }


        // Store API Keys
        Installation::updateOrCreate(
            ['license_id' => $license->id],
            [
                'consumer_key'    => $request->consumer_key,
                'consumer_secret' => $request->consumer_secret,
                // 'site_url'        => $request->site_url,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'API keys synced successfully.',
        ], 200);
    }
}
