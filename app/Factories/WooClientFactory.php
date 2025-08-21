<?php

namespace App\Factories;

use App\Services\WooClient;
use App\Models\Installation;
use App\Services\Touch365Api;
use Automattic\WooCommerce\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WooClientFactory
{
    public static function make($installationId): WooClient
    {
        $creds = Installation::findOrFail($installationId);
        $client = new Client(
            $creds->site_url,
            $creds->consumer_key,
            $creds->consumer_secret,
            ['version' => 'wc/v3', 'verify_ssl' => false]
        );

        return new WooClient($client, $creds);
    }
}
