<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Models\Department;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    protected $DepartmentRepository;
    public function __construct(
        DepartmentRepositoryInterface $departmentRepositoryInterface
    ) {
        $this->DepartmentRepository = $departmentRepositoryInterface;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = $this->DepartmentRepository->departments();
        return response()->json($departments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreDepartmentRequest $request)
    {
        // return $request;
        $validated = $request->validated();

        // Access validated data:
        $departments = $validated['stockdepartmentmaster'];
        $subdepartments = $validated['stocksubdepartmentmaster'];

        // Save logic here, e.g.:
        $this->DepartmentRepository->store($departments, $subdepartments, $request->installation_id);

        return response()->json([
            'message' => 'Departments and Subdepartments created successfully',
            'data' => $validated,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
