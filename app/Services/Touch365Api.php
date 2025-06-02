<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class Touch365Api
{
    protected string $username;
    protected string $password;
    protected string $tenant;
    protected string $url;
    protected ?string $token = null;
    public ?string $message = null;
    protected Client $client;

    /**
     * @throws Exception
     */
    public function __construct(string $username, string $password, string $tenant, string $url)
    {
        $this->username = htmlspecialchars($username);
        $this->password = htmlspecialchars($password);
        $this->tenant   = htmlspecialchars($tenant);
        $this->url      = rtrim(filter_var($url, FILTER_VALIDATE_URL), '/');

        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout'  => 10.0,
        ]);

        if (!$this->authenticate()) {
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
    public function get(string $endpoint, array $queries = []): string
    {
        try {
            $response = $this->client->request('GET', $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'AuthToken'    => $this->token,
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
                    'AuthToken'    => $this->token,
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
            $response = $this->client->request('POST', '/api/auth', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username'  => $this->username,
                    'password'  => $this->password,
                    'tenant'    => $this->tenant,
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
            return true;
        } catch (RequestException $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
        }
    }
}
