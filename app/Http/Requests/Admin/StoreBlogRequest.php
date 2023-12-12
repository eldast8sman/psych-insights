<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogRequest extends FormRequest
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
            'duration' => 'string',
            'categories' => 'array|nullable',
            'categories.*' => 'integer|exists:blog_categories,id',
            'body' => 'required|string',
            'photo' => 'required|file|mimes:png,jpg,jpeg,gif|max:500',
            'author' => 'string|nullable'
        ];
    }
}
