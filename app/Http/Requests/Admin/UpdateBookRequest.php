<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
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
            'author' => 'required|string',
            'summary' => 'string|nullable',
            'price' => 'numeric|nullable',
            'publication_year' => 'integer|nullable|max:9999',
            'subscription_level' => 'integer|min:0',
            'book_cover' => 'file|mimes:png,jpg|max:500|nullable',
            'purchase_link' => 'required|string|url'
        ];
    }
}
