<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use App\Jobs\PushWooOrderToPOSJob;
use Illuminate\Support\Facades\Log;

class WooOrderWebhookController extends Controller
{
    public function __invoke(Request $request, string $licenseKey)
    {
        $order = $request->all();

        Log::info('🔔 Incoming WooCommerce webhook received.', [
            'license_key' => $licenseKey,
            'order_id'    => $order['id'] ?? null,
            'raw_payload' => $order,
        ]);

        try {
            $installationId = $this->resolveInstallation($licenseKey);

            dispatch(new PushWooOrderToPOSJob($order, $installationId));

            Log::info('✅ Order dispatched to POS sync queue.', [
                'order_id' => $order['id'] ?? null,
                'installation_id' => $installationId,
            ]);

            return response()->json(['status' => 'Order Sync Queued']);
        } catch (\Throwable $e) {
            Log::error('❌ Order sync failed at webhook.', [
                'license_key' => $licenseKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Order sync failed.'], 500);
        }
    }

    protected function resolveInstallation(string $licenseKey): int
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

        if (!$license->installation || $license->installation->status !== 'active') {
            Log::warning('⚠️ Installation not found or inactive for license.', [
                'license_id' => $license->id,
                'license_key' => $licenseKey
            ]);
            abort(404, 'Active installation not found for this license.');
        }

        return $license->installation->id;
    }
}
