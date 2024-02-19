<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetGoalRequest extends FormRequest
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
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:goal_plan_questions,id',
            'answers.*.answer' => 'required'
        ];
    }
}
