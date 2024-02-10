<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalReflectionRequest extends FormRequest
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
            'reflections' => 'required|array',
            'reflections.*.title' => 'required|string',
            'reflections.*.pre_text' => 'string|nullable',
            'reflections.*.type' => 'required|string',
            'reflections.*.post_text' => 'string|nullable',
            'reflections.*.min' => 'required_if:reflections.*.type,range|integer',
            'reflections.*.max' => 'required_if:reflections.*.type,range|integer'
        ];
    }
}
