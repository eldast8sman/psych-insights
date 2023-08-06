<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPackageRequest extends FormRequest
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
            'package' => 'required|string|unique:subscription_packages,package',
            'level' => 'required|integer',
            'podcast_limit' => 'required|integer|min:-1',
            'article_limit' => 'required|integer|min:-1',
            'audio_limit' => 'required|integer|min:-1',
            'video_limit' => 'required|integer|min:-1',
            'book_limit' => 'required|integer|min:-1',
            'first_time_promo' => 'required|numeric|min:0|max:100',
            'subsequent_promo' => 'required|numeric|min:0|max:100',
            'payment_plans' => 'required|array',
            'payment_plans.*.amount' => 'required|numeric|min:1',
            'payment_plans.*.duration_type' => 'required|string',
            'payment_plans.*.duration' => 'required|integer'
        ];
    }
}
