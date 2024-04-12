<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearnAndDoActivityRequest extends FormRequest
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
            'overview' => 'string|nullable',
            'post_text' => 'string|nullable',
            'example' => 'string|nullable',
            'questions' => 'array|nullable',
            'questions.*.question' => 'required|string',
            'questions.*.answer_type' => 'required|string',
            'questions.*.number_of_list' => 'required_if:questions.*.answer_type,list|integer|min:1|nullable',
        ];
    }
}
