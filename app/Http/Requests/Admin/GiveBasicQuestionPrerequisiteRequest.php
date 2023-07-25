<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiveBasicQuestionPrerequisiteRequest extends FormRequest
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
            'prerequisite_id' => 'required|integer|exists:basic_questions,id',
            'prerequisite_value' => 'required|integer|min:1',
            'action' => 'required|string',
            'default_value' => 'required|integer|min:1'
        ];
    }
}
