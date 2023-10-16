<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePodcastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'summary' => 'string|nullable',
            'release_date' => 'required|date',
            'subscription_level' => 'required|integer',
            'cover_art' => 'file|mimes:png,jpg,jpeg,gif|max:500|nullable',
            'podcast_link' => 'required|url'
        ];
    }
}
