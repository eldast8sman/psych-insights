<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearnAndDoQuestionRequest extends FormRequest
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
            'answer_type' => 'required|string',
            'number_of_list' => 'required_if:answer_type,list',
            'minimum' => 'integer|min:0|nullable',
            'maximum' => 'integer|nullable'
        ];
    }
}
