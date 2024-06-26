<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAudioRequest extends FormRequest
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
            'tag' => 'string|nullable',
            'description' => 'string|nullable',
            'subscription_level' => 'required|integer|min:0',
            'release_date' => 'date|nullable',
            'audio_file' => 'file|mimes:mp3,mpeg3,wav,aac|max:20000|nullable',
            'photo' => 'file|mimes:png,jpg,jpeg,gif|max:500|nullable'
        ];
    }
}
