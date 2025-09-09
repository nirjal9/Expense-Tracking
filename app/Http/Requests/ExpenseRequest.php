<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
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

        if ($routeName === 'expenses.index') {
            return [
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d'],
                'search' => ['nullable', 'string', 'max:255'],
                'category_id' => ['nullable', 'exists:categories,id'],
                'min_amount' => ['nullable', 'numeric', 'min:1'],
                'max_amount' => ['nullable', 'numeric', 'min:1', 'gte:min_amount'],
                'page' => ['nullable', 'integer', 'min:1'], // Allow pagination
            ];
        }

        return [
            'category_id'=>'required',
            'amount'=>'required|numeric|min:1|max:999999999.99',
            'description'=>'nullable|string|',
            'date'=>['required','date','before:tomorrow']
        ];
    }
    public function messages(): array
    {
        $routeName = $this->route()->getName();

        if ($routeName === 'expenses.index') {
            return [
                'start_date.date_format' => 'The start date must be in YYYY-MM-DD format.',
                'end_date.date_format' => 'The end date must be in YYYY-MM-DD format.',
                'search.max' => 'The search term cannot exceed 255 characters.',
                'category_id.exists' => 'The selected category is invalid.',
                'min_amount.numeric' => 'The minimum amount must be a number.',
                'min_amount.min' => 'The minimum amount cannot be negative.',
                'max_amount.numeric' => 'The maximum amount must be a number.',
                'max_amount.min' => 'The maximum amount cannot be negative.',
                'max_amount.gte' => 'The maximum amount must be greater than or equal to the minimum amount.',
            ];
        }

        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 1.',
            'amount.max' => 'The amount exceeds the maximum allowable.',
//            'description.required' => 'The description field is required.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date must be a valid date.',
        ];
    }
}
