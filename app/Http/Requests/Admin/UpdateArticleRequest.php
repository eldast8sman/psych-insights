<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
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
            'categories' => 'required|array',
            'subscription_level' => 'required|integer|min:0',
            'author' => 'required|string',
            'duration' => 'integer|nullable',
            'publication_date' => 'date|nullable',
            'content' => 'required|string',
            'photo' => 'file|mimes:png,jpg,jpeg,gif|max:500|nullable'
        ];
    }
}
