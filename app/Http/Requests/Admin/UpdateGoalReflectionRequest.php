<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalReflectionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'pre_text' => 'string|nullable',
            'type' => 'required|string',
            'post_text' => 'string|nullable',
            'min' => 'required_if:type,range|integer',
            'max' => 'required_if:type,range|integer'
        ];
    }
}
