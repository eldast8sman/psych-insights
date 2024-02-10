<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalPlanQuestionRequest extends FormRequest
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
            'goal_questions' => 'required|array',
            'goal_questions.*.title' => 'required|string',
            'goal_questions.*.pre_text' => 'string|nullable',
            'goal_questions.*.example' => 'string|nullable',
            'goal_questions.*.weekly_plan' => 'required|boolean'
        ];
    }
}
