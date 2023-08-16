<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
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
            'description' => 'string|nullable',
            'subscription_level' => 'required|integer|min:0',
            'categories' => 'required|array',
            'categories.*' => 'required|integer|exists:categories,id',
            'duration' => 'integer|nullable',
            'photo' => 'file|mimes:png,jpg,jpeg,gif|max:200|nullable',
            'video' => 'required|file|mimes:mp4,mpeg4,avi,mkv,mov|max:50000',
            'release_date' => 'date|nullable'
        ];
    }
}
