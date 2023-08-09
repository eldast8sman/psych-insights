<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePodcastRequest extends FormRequest
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
            'author' => 'required|string',
            'categories' => 'required|array',
            'categories.*' => 'required|integer|exists:categories,id',
            'summary' => 'string|nullable',
            'release_date' => 'required|date',
            'subscription_level' => 'required|integer',
            'cover_art' => 'file|mimes:png,jpg,jpeg,gif|max:200|nullable',
            'podcast_link' => 'required|string|url'
        ];
    }
}
