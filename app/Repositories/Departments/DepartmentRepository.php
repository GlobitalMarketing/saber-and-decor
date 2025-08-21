<?php
namespace App\Repositories\Departments;

use App\Models\Department;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Installation;
use App\Models\SubDepartment;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    /**
     * Store departments and subdepartments in the database.
     *
     * @param array $departments
     * @param array $subdepartments
     * @return void
     */
    public function store(array $departments, array $subdepartments, ?int $installationId = null)
    {
        // Pre-group subdepartments by DEPARTMENTCODE
        $subMap = [];
        foreach ($subdepartments as $sub) {
            $subMap[$sub['DEPARTMENTCODE']][] = $sub;
        }
        
        foreach ($departments as $department) {
            $existing = Department::where('code', $department['DEPARTMENTCODE'])->first();

            $parentDepartment = Department::updateOrCreate(
                ['code' => $department['DEPARTMENTCODE']],
                [
                    'name' => $department['DEPARTMENTNAME'],
                    'installation_id' => $installationId
                ]
            );
            
            
            $this->logDepartmentChange(
                $existing ? [$existing->only(['code', 'name'])] : [],
                [
                    [
                        'code' => $parentDepartment->code,
                        'name' => $parentDepartment->name
                    ]
                ],
                $parentDepartment->id,
                $installationId
            );
            
            
            foreach (($subMap[$department['DEPARTMENTCODE']] ?? []) as $subdepartment) {
                $existingSub = Department::where('code', $subdepartment['SUBDEPARTMENTCODE'])->first();

                $childDepartment = Department::updateOrCreate(
                    ['code' => $subdepartment['SUBDEPARTMENTCODE']],
                    [
                        'name' => $subdepartment['SUBDEPARTMENTNAME'],
                        'parent_id' => $parentDepartment->id,
                        'installation_id' => $installationId,
                    ]
                );

                $this->logDepartmentChange(
                    $existingSub ? [$existingSub->only(['code', 'name'])] : [],
                    [
                        [
                            'code' => $childDepartment->code,
                            'name' => $childDepartment->name
                        ]
                    ],
                    $childDepartment->id,
                    $installationId
                );
            }
        }
    }


    public function getDepartmentsByInstallation($installationId){
        $installation = Installation::findIfActive($installationId)->first();
        if ($installation) {
            // Proceed with logic
            $departments = Department::where('installation_id', $installationId)
                ->whereNull('parent_id')
                ->pluck('name', 'code')->toArray();

            return $departments;
        } else {
            return [];
        }
    }

    public function getSubDepartmentsByInstallation($installationId){
        $installation = Installation::findIfActive($installationId)->first();
        if ($installation) {
            return Department::with('parent:id,code')
                ->where('installation_id', $installationId)
                ->whereNotNull('parent_id')
                ->pluck('name', 'code')->toArray();
        } else {
            return collect();
        }
    }
    /**
     * Get paginated departments with optional eager loaded subdepartments.
     *
     * @param int $perPage
     *
     * @throws \Exception on DB failure
     */
    public function departments(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Department::with(['subdepartments', 'installation'])->paginate($perPage);
        } catch (\Exception $e) {
            // Log here if you want, or just rethrow
            throw new \Exception('Failed to fetch departments: ' . $e->getMessage(), 0, $e);
        }
    }

    public function logDepartmentChange(array $oldData, array $newData, ?int $departmentId = null, $installationId = null)
    {
        $changed = [];

        foreach ($newData as $newItem) {
            $oldItem = collect($oldData)->firstWhere('code', $newItem['code']);

            if (!$oldItem) {
                $changed[] = [
                    'old' => null,
                    'attributes' => $newItem,
                ];
            } elseif ($oldItem != $newItem) {
                $changed[] = [
                    'old' => $oldItem,
                    'attributes' => $newItem,
                ];
            }
        }

        if (!empty($changed)) {
            // Determine event type based on whether any old items existed
            $hasOld = collect($changed)->contains(fn ($item) => !is_null($item['old']));
            $eventType = $hasOld ? 'update' : 'create';

            try {
                Activity::create([
                    'log_name' => 'department',
                    'description' => 'Departments updated',
                    'subject_type' => \App\Models\Department::class,
                    'subject_id' => $departmentId,
                    'causer_type' => 'user',
                    'causer_id' => $installationId,
                    'event' => $eventType,
                    'batch_uuid' => (string) Str::uuid(),
                    'properties' => ['changes' => $changed],
                ]);
            } catch (\Throwable $e) {
                \Log::error('Activity log failed: ' . $e->getMessage());
            }
        }
    }
}
