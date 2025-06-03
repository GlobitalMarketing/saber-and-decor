<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class Touch365Api
{
    protected string $username;
    protected string $password;
    protected string $tenant;
    protected string $url;
    protected ?string $token = null;
    protected int $tokenExpireTime = 3600; // Token expiration time in seconds (1 hour by default)
    public ?string $message = null;
    protected Client $client;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->username = htmlspecialchars(env('TOUCH365_USERNAME'));
        $this->password = htmlspecialchars(env('TOUCH365_PASSWORD'));
        $this->tenant = htmlspecialchars(env('TOUCH365_TENANT'));
        $this->url = rtrim(filter_var(env('TOUCH365_URL'), FILTER_VALIDATE_URL), '/');

        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout' => 10.0,
        ]);

        // Authenticate only if no token is cached or token is expired
        if (!$this->getToken()) {
            throw new Exception("touch365 API authentication error");
        }
    }

    /**
     * @throws Exception
     */
    private function handleResponseCode(int $code): void
    {
        switch ($code) {
            case 400:
                throw new Exception("Bad Request");
            case 401:
                throw new Exception("Unauthorized");
            case 403:
                throw new Exception("Forbidden");
            case 404:
                throw new Exception("Not Found");
            case 500:
                throw new Exception("Internal Server Error");
            default:
                $this->message = "Status $code";
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function get(string $endpoint, array $queries = [])
    {
        try {
            $response = $this->client->request('GET', $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'AuthToken' => $this->getToken(),
                ],
                'query' => $queries,
                'http_errors' => false,
                'allow_redirects' => true,
            ]);

            $this->handleResponseCode($response->getStatusCode());

            return (string) $response->getBody();

        } catch (RequestException $e) {
            throw new Exception("GET request failed: " . $e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function post(string $endpoint, array $queries = [], array $data = []): string
    {
        try {
            $response = $this->client->request('POST', $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'AuthToken' => $this->getToken(),
                ],
                'query' => $queries,
                'json' => $data,
                'http_errors' => false,
                'allow_redirects' => true,
            ]);

            $this->handleResponseCode($response->getStatusCode());

            return (string) $response->getBody();
        } catch (RequestException $e) {
            throw new Exception("POST request failed: " . $e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function authenticate(): bool
    {
        try {
            $url = env('TOUCH365_URL', 'https://touch365api.co.za');

            $response = $this->client->request('POST', $url . '/api/auth', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
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

            // Cache the token with expiration time
            Cache::put('touch365_token', $this->token, $this->tokenExpireTime);

            \Log::info("Token: " . $this->token);
            return true;
        } catch (RequestException $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
        }
    }

    /**
     * Get the stored token from cache or authenticate if expired.
     *
     * @return string
     * @throws Exception
     */
    public function getToken(): string
    {
        // Check if token exists in cache and is not expired
        if (Cache::has('touch365_token')) {
            return Cache::get('touch365_token');
        }

        // If token is missing or expired, re-authenticate
        if (!$this->authenticate()) {
            throw new Exception("Failed to authenticate and retrieve a valid token.");
        }

        return $this->token;
    }
}
