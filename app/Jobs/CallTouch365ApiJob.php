<?php
namespace App\Jobs;

use App\Repositories\Departments\DepartmentRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class CallTouch365ApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $apiEndpoint;

    /**
     * Create a new job instance.
     */
    public function __construct(string $apiEndpoint = '/api/department')
    {
        $this->apiEndpoint = $apiEndpoint; // Store the endpoint as a property
    }

    /**
     * Execute the job.
     */
    public function handle(DepartmentRepositoryInterface $departmentRepository)
    {
        Log::info("job run");
        try {
            // Resolve the token from the cache
            $token = Cache::get('touch365_token');
            if (!$token) {
                Log::error('No token found in cache.');
                return;
            }

            // Create a new Guzzle client inside the handle method (avoiding serialization issues)
            $client = new Client([
                'base_uri' => rtrim(env('TOUCH365_URL'), '/'), // Base URL for the API
                'timeout' => 10.0,
            ]);

            // Perform the GET request to fetch the department data
            $response = $client->request('GET', $this->apiEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'AuthToken' => $token,
                ],
                'http_errors' => false,  // Prevent errors from throwing exceptions automatically
                'allow_redirects' => true,
            ]);

            // Check if the response was successful
            $this->handleResponseCode($response->getStatusCode());

            $responseBody = (string) $response->getBody();  // Get the body of the response as string
            $responseJson = json_decode($responseBody, true);

            // Log the response body as a JSON string
            // Log::info('API Response: ' . json_encode($responseJson, JSON_PRETTY_PRINT));
            if (isset($responseJson['stockdepartmentmaster']) && isset($responseJson['stocksubdepartmentmaster'])) {
                $departments = $responseJson['stockdepartmentmaster'];
                $subdepartments = $responseJson['stocksubdepartmentmaster'];
                $departmentRepository->storeDepartments($departments, $subdepartments);
                Log::info('Departments and subdepartments stored successfully.');
            } else {
                Log::error('Invalid data received from API: ' . $responseBody);
            }

        } catch (Exception $e) {
            // Handle exceptions and log the error
            Log::error('Job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle the response status code and log appropriate messages.
     */
    private function handleResponseCode(int $code): void
    {
        switch ($code) {
            case 200:
                Log::info('API request was successful.');
                break;
            case 400:
                Log::error('Bad request error (400).');
                break;
            case 401:
                Log::error('Unauthorized error (401).');
                break;
            case 403:
                Log::error('Forbidden error (403).');
                break;
            case 404:
                Log::error('Not found error (404).');
                break;
            case 500:
                Log::error('Internal server error (500).');
                break;
            default:
                Log::error('API error with status code: ' . $code);
        }
    }
}
