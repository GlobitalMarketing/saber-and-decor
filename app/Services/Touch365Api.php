<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Touch365Api
{
    protected string $username;
    protected string $password;
    protected string $tenant;
    protected string $url;
    protected ?string $token = null;
    protected int $tokenExpireTime = 3600; // seconds
    protected Client $client;

    /**
     * Constructor: initialize client and authenticate
     *
     * @throws Exception
     */
    public function __construct(string $username, string $password, string $tenant)
    {
        // $this->username = htmlspecialchars(env('TOUCH365_USERNAME'));
        // $this->password = htmlspecialchars(env('TOUCH365_PASSWORD'));
        // $this->tenant = htmlspecialchars(env('TOUCH365_TENANT'));
        $this->username = $username;
        $this->password = $password;
        $this->tenant = $tenant;
        $this->url = rtrim(filter_var("https://touch365api.co.za", FILTER_VALIDATE_URL), '/');

        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout' => 10.0,
            'verify' => false,
        ]);
        // Preload token or authenticate if none found
        $this->token = $this->getToken();
        if (!$this->token) {
            throw new Exception("touch365 API authentication error");
        }
    }

    /**
     * Generic API call method supporting all HTTP verbs.
     *
     * @param string $method HTTP method ('GET', 'POST', 'PUT', 'DELETE')
     * @param string $endpoint API endpoint (e.g., '/api/department')
     * @param array $query Query parameters for GET/DELETE
     * @param array $data JSON body for POST/PUT
     * @return array|string Response decoded to array if JSON, or raw string if not JSON
     * @throws Exception
     */
    public function call(string $method, string $endpoint, array $query = [], array $data = [])
    {
        $method = strtoupper($method);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'AuthToken' => $this->getToken(),
            ]
        ];

        if (in_array($method, ['GET', 'DELETE'])) {
            if (!empty($query)) {
                $options['query'] = $query;
            }
        }

        if (in_array($method, ['POST'])) {
            try {
                $response = $this->client->post($endpoint, [
                    'headers' => [
                        'AuthToken' => $this->getToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);

                $body = $response->getBody();
                $data = json_decode($body, true);

                Log::info('Touch365 API Response:', $data);
                return $data;

            } catch (\Exception $e) {
                Log::error('Touch365 API Error: ' . $e->getMessage());
            }

        }
        if (in_array($method, ['PUT'])) {
            if (!empty($query)) {
                $options['query'] = $query;
            }
            if (!empty($data)) {
                $options = $data; // ✅ correct way
            }
        }

        Log::info("Request Options:", $options);

        $response = $this->client->request($method, $endpoint, $options);

        $this->handleResponseCode($response->getStatusCode());

        $body = (string) $response->getBody();

        $decoded = json_decode($body, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $body;
    }


    /**
     * Authenticate and cache token
     *
     * @return bool
     * @throws Exception
     */
    public function authenticate(): bool
    {
        try {
            $response = $this->client->request('POST', '/api/auth', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'tenant' => $this->tenant,
                    'appsource' => 'woo_sync',
                ],
                'http_errors' => false,
                'allow_redirects' => true,
            ]);

            $this->handleResponseCode($response->getStatusCode());

            $data = json_decode((string) $response->getBody());

            if (!isset($data->token)) {
                throw new Exception("Authentication token not found in response");
            }

            $this->token = $data->token;

            // Cache the token for expiry period
            Cache::put('touch365_token_' . $this->tenant, $this->token, $this->tokenExpireTime);

            \Log::info("Touch365 API authenticated; token cached.");

            return true;
        } catch (RequestException $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
        }
    }

    /**
     * Get token from cache or authenticate if missing/expired
     *
     * @return string|null
     * @throws Exception
     */
    public function getToken(): ?string
    {
        if (Cache::has('touch365_token_' . $this->tenant)) {
            Log::info(Cache::get('touch365_token_' . $this->tenant));
            return Cache::get('touch365_token_' . $this->tenant);
        }

        if ($this->authenticate()) {
            return $this->token;
        }

        return null;
    }

    /**
     * Handle HTTP response codes and throw exceptions for errors
     *
     * @param int $code
     * @throws Exception
     */
    private function handleResponseCode(int $code): void
    {
        switch ($code) {
            case 200:
            case 201:
                return; // Success
            case 400:
                throw new Exception("Bad Request (400)");
            case 401:
                throw new Exception("Unauthorized (401)");
            case 403:
                throw new Exception("Forbidden (403)");
            case 404:
                throw new Exception("Not Found (404)");
            case 500:
                throw new Exception("Internal Server Error (500)");
            default:
                throw new Exception("Unexpected HTTP status code: $code");
        }
    }
}
