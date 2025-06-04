<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'stockdepartmentmaster' => 'required|array|min:1',
            'stockdepartmentmaster.*.DEPARTMENTCODE' => 'required|string|min:1',
            'stockdepartmentmaster.*.DEPARTMENTNAME' => 'required|string|min:1',

            'stocksubdepartmentmaster' => 'required|array|min:1',
            'stocksubdepartmentmaster.*.DEPARTMENTCODE' => 'required|string|min:1',
            'stocksubdepartmentmaster.*.SUBDEPARTMENTCODE' => 'required|string|min:1',
            'stocksubdepartmentmaster.*.SUBDEPARTMENTNAME' => 'required|string|min:1',
        ];
    }

    public function messages()
    {
        return [
            'stockdepartmentmaster.required' => 'Department master data is required.',
            'stockdepartmentmaster.*.DEPARTMENTCODE.required' => 'Department code is required and cannot be empty.',
            'stockdepartmentmaster.*.DEPARTMENTNAME.required' => 'Department name is required and cannot be empty.',

            'stocksubdepartmentmaster.required' => 'Subdepartment master data is required.',
            'stocksubdepartmentmaster.*.DEPARTMENTCODE.required' => 'Department code in subdepartment is required and cannot be empty.',
            'stocksubdepartmentmaster.*.SUBDEPARTMENTCODE.required' => 'Subdepartment code is required and cannot be empty.',
            'stocksubdepartmentmaster.*.SUBDEPARTMENTNAME.required' => 'Subdepartment name is required and cannot be empty.',
        ];
    }
}
