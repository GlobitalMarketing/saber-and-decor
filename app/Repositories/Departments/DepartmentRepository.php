<?php
namespace App\Repositories\Departments;

use App\Models\Department;
use App\Models\SubDepartment;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    /**
     * Store departments and subdepartments in the database.
     *
     * @param array $departments
     * @param array $subdepartments
     * @return void
     */
    public function storeDepartments(array $departments, array $subdepartments)
    {
        // Store parent departments
        foreach ($departments as $department) {
            // Insert or update the parent department
            $parentDepartment = Department::updateOrCreate(
                ['code' => $department['DEPARTMENTCODE']],
                ['name' => $department['DEPARTMENTNAME']]
            );

            // Handle the subdepartments associated with this parent department
            foreach ($subdepartments as $subdepartment) {
                if ($subdepartment['DEPARTMENTCODE'] === $department['DEPARTMENTCODE']) {
                    // Insert or update subdepartment and link it to the parent department
                    Department::updateOrCreate(
                        ['code' => $subdepartment['SUBDEPARTMENTCODE']],
                        [
                            'name' => $subdepartment['SUBDEPARTMENTNAME'],
                            'parent_id' => $parentDepartment->id  // Link subdepartment to parent department
                        ]
                    );
                }
            }
        }
    }
}
