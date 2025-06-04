<?php

namespace App\Jobs;

use App\Repositories\Departments\DepartmentRepositoryInterface;
use App\Services\Touch365Api;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\Log;

class CallTouch365ApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Touch365Api $touch365Api;
    protected DepartmentRepositoryInterface $departmentRepository;

    protected string $apiEndpoint;

    /**
     * Create a new job instance.
     *
     * @param Touch365Api $touch365Api
     * @param DepartmentRepositoryInterface $departmentRepository
     * @param string $apiEndpoint
     */
    public function __construct(
        // Touch365Api $touch365Api,
        // DepartmentRepositoryInterface $departmentRepository,
        string $apiEndpoint = '/api/department'
    ) {
        // $this->touch365Api = $touch365Api;
        // $this->departmentRepository = $departmentRepository;
        $this->apiEndpoint = $apiEndpoint;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CallTouch365ApiJob started.');

        try {
            // Use the dynamic call method with GET
            $response = $this->touch365Api->call('GET', $this->apiEndpoint);

            if (
                isset($response['stockdepartmentmaster']) &&
                isset($response['stocksubdepartmentmaster'])
            ) {
                $departments = $response['stockdepartmentmaster'];
                $subdepartments = $response['stocksubdepartmentmaster'];

                $this->departmentRepository->store($departments, $subdepartments);

                Log::info('Departments and subdepartments stored successfully.');
            } else {
                Log::error('Invalid data received from Touch365 API.', ['response' => $response]);
            }
        } catch (Exception $e) {
            Log::error('CallTouch365ApiJob failed: ' . $e->getMessage());
            // Optionally rethrow or handle failure logic here
        }
    }
}
