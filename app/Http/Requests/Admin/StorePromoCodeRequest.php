<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoCodeRequest extends FormRequest
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
            'promo_code' => 'required|string|unique:promo_codes,promo_code',
            'percentage_off' => 'required|numeric|min:1|max:100',
            'usage_limit' => 'required|integer|min:1',
            'total_limit' => 'required|integer|min:-1'
        ];
    }
}
