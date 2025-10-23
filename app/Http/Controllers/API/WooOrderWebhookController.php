<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use App\Jobs\PushWooOrderToPOSJob;
use Illuminate\Support\Facades\Log;

class WooOrderWebhookController extends Controller
{
    /**
     * Handle incoming WooCommerce webhook.
     */
    public function receiveWebhook(Request $request, string $licenseKey)
    {
        $order = $request->all();

        Log::info('🔔 Incoming WooCommerce webhook received.', [
            $order,
        ]);

        // try {
        $installationId = $this->resolveInstallation($licenseKey);

        dispatch(new PushWooOrderToPOSJob($order, $installationId));

        Log::info('✅ Order dispatched to POS sync queue.', [
            'order_id' => $order['id'] ?? null,
            'installation_id' => $installationId,
        ]);

        return response()->json([
            'status' => 'Order Sync Queued',
            'order_id' => $order['id'] ?? null
        ]);
        // } catch (\Throwable $e) {
        //     Log::error('❌ Order sync failed at webhook.', [
        //         'license_key' => $licenseKey,
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //     ]);

        //     return response()->json([
        //         'error' => 'Order sync failed.',
        //         'message' => $e->getMessage()
        //     ], 500);
        // }
    }

    /**
     * Validate license and return installation ID.
     */
    private function resolveInstallation(string $licenseKey)
    {
        $license = License::where('license_key', $licenseKey)
            ->where('status', 'active')
            ->with('installation')
            ->first();
        if (!$license) {
            Log::warning('⚠️ License key not found or inactive.', [
                'license_key' => $licenseKey
            ]);
            abort(404, 'License key invalid or inactive.');
        }
        // return $license->installation->status;

        if (!$license->installation) {
            Log::warning('⚠️ Installation not found or inactive for license.', [
                'license_id' => $license->id,
                'license_key' => $licenseKey
            ]);
            abort(404, 'Active installation not found for this license.');
        }

        return $license->installation->id;
    }
}
