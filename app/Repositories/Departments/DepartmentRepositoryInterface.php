<?php
namespace App\Repositories\Departments;

interface DepartmentRepositoryInterface
{
    // public function syncTouch365Departments(array $departments, array $subdepartments);
    public function store(array $departments, array $subdepartments, ?int $installationId = null);
    public function departments();
}
