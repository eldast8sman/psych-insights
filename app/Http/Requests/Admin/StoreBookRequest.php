<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'categories.*' => 'required|integer|exists:categories,id',
            'author' => 'required|string',
            'summary' => 'string|nullable',
            'price' => 'numeric|nullable',
            'publication_year' => 'integer|nullable',
            'subscription_level' => 'integer|min:0',
            'book_cover' => 'file|mimes:png,jpg|max:200|nullable'
        ];
    }
}
