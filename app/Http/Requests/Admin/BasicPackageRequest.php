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
            'video_limit' => 'required|integer|min:0',
            'book_limit' => 'required|integer|min:0',
            'listen_and_learn_limit' => 'required|integer|min:-1',
            'read_and_reflect_limit' => 'required|integer|min:-1',
            'learn_and_do_limit' => 'required|integer|min:-1'
        ];
    }
}
