<?php
namespace App\Repositories\Departments;

interface DepartmentRepositoryInterface
{
    public function storeDepartments(array $departments, array $subdepartments);
}
