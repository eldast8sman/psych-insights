<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicQuestionRequest extends FormRequest
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
            'question' => 'required|string',
            'categories' => 'required|array',
            'is_k10' => 'required|boolean',
            'special_options' => 'required|boolean',
            'options' => 'required_if:special_options,1|array',
            'options.*.id' => 'nullable|exists:basic_question_special_options,id',
            'options.*.option' => 'required|string',
            'options.*.value' => 'required|integer'
        ];
    }
}
