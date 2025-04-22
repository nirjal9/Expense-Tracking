<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
    public function rules(): array
    {
        $routeName = $this->route()->getName();

        if ($routeName === 'categories.index') {
            return [
                'search' => ['nullable', 'string', 'max:255'],
                'budget_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ];
        }

        if ($routeName === 'categories.store') {
            return [
                'predefined_category' => ['nullable', 'exists:categories,id'],
                'name' => [
                    'nullable',
                    'required_without:predefined_category',
                    'string',
                    'max:255',

                ],
                'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ];
        }

        if ($routeName === 'categories.update') {
            return [
                'name' => ['required', 'string', 'max:255'],
                'budget_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ];
        }

        return [
            'name' => 'required|string|max:255|',
            'budget_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $routeName = $this->route()->getName();

        if ($routeName === 'categories.index') {
            return [
                'search.max' => 'The search term cannot exceed 255 characters.',
                'budget_percentage.numeric' => 'The budget percentage must be a number.',
                'budget_percentage.min' => 'The budget percentage cannot be negative.',
                'budget_percentage.max' => 'The budget percentage cannot exceed 100%.',
            ];
        }

        return [
            'name.required' => 'The category name is required.',
            'name.string' => 'The category name must be text.',
            'name.max' => 'The category name cannot exceed 255 characters.',
            'name.unique' => 'This category name already exists.',
            'budget_percentage.required' => 'The budget percentage is required.',
            'budget_percentage.numeric' => 'The budget percentage must be a number.',
            'budget_percentage.min' => 'The budget percentage cannot be negative.',
            'budget_percentage.max' => 'The budget percentage cannot exceed 100%.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'predefined_category.exists' => 'The selected predefined category does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'category name',
            'budget_percentage' => 'budget percentage',
            'description' => 'description',
            'search' => 'search term',
            'budget_percentage.*' => 'budget percentage',
            'name.*' => 'category name',
            'description.*' => 'description',
            'predefined_category' => 'predefined category',
        ];
    }
}
