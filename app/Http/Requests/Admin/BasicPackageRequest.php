<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BasicPackageRequest extends FormRequest
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
            'podcast_limit' => 'required|integer|min:0',
            'article_limit' => 'required|integer|min:0',
            'audio_limit' => 'required|integer|min:0',
            'video_limit' => 'required|integer|min:0'
        ];
    }
}
