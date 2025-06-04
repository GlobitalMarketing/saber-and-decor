<?php
namespace App\Repositories\Departments;

use App\Models\Department;
use App\Models\SubDepartment;
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
        // return $installationId; 
        // Store parent departments
        foreach ($departments as $department) {
            // Insert or update the parent department
            $parentDepartment = Department::updateOrCreate(
                ['code' => $department['DEPARTMENTCODE']],
                [
                    'name' => $department['DEPARTMENTNAME'],
                    'installation_id' => $installationId
                ],

            );

            // Handle the subdepartments associated with this parent department
            foreach ($subdepartments as $subdepartment) {
                if ($subdepartment['DEPARTMENTCODE'] === $department['DEPARTMENTCODE']) {
                    // Insert or update subdepartment and link it to the parent department
                    Department::updateOrCreate(
                        ['code' => $subdepartment['SUBDEPARTMENTCODE']],
                        [
                            'name' => $subdepartment['SUBDEPARTMENTNAME'],
                            'parent_id' => $parentDepartment->id,
                            'installation_id' => $installationId,
                        ]
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
            return Department::with(['subdepartments','installation'])->paginate($perPage);
        } catch (\Exception $e) {
            // Log here if you want, or just rethrow
            throw new \Exception('Failed to fetch departments: ' . $e->getMessage(), 0, $e);
        }
    }
}
