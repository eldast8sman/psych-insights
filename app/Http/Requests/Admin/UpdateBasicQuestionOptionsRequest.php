<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicQuestionOptionsRequest extends FormRequest
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
            'options' => 'required|array',
            'options.*.id' => 'required|integer|exists:basic_question_options,id',
            'options.*.option' => 'required|string',
            'options.*.value' => 'required|integer'
        ];
    }
}
