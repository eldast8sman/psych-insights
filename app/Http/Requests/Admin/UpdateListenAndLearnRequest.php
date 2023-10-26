<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListenAndLearnRequest extends FormRequest
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
            'categories' => 'required|array',
            'overview' => 'required|string',
            'audio_overview' => 'required|string',
            'photo' => 'file|mimes:png,jpg,jjpeg,gif|max:500',
            'subscription_level' => 'integer|min:0',
        ];
    }
}
