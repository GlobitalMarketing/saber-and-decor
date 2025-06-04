<?php
namespace App\Repositories\Departments;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
        foreach ($departments as $department) {
            $existing = Department::where('code', $department['DEPARTMENTCODE'])->first();

            $parentDepartment = Department::updateOrCreate(
                ['code' => $department['DEPARTMENTCODE']],
                [
                    'name' => $department['DEPARTMENTNAME'],
                    'installation_id' => $installationId
                ]
            );

            // Log if data has changed
            $this->logDepartmentChange(
                $existing ? [$existing->only(['code', 'name'])] : [],
                [ // current data
                    [
                        'code' => $parentDepartment->code,
                        'name' => $parentDepartment->name
                    ]
                ],
                $parentDepartment->id,
                $installationId
            );

            foreach ($subdepartments as $subdepartment) {
                if ($subdepartment['DEPARTMENTCODE'] == $department['DEPARTMENTCODE']) {
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
    function logDepartmentChange(array $oldData, array $newData, ?int $departmentId = null, $installationId)
    {
        $changed = [];
        foreach ($newData as $key => $newItem) {
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
            Activity::create([
                'log_name' => 'department',
                'description' => 'Departments updated',
                'subject_type' => Department::class,
                'subject_id' => $departmentId,
                'causer_type' => 'user',
                'causer_id' => $installationId,
                'event' => !$oldItem?'update':'create',
                'batch_uuid' => (string) Str::uuid(),
                'properties' => $changed,
            ]);
        }
    }
}
