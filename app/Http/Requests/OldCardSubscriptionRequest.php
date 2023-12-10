<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OldCardSubscriptionRequest extends FormRequest
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
            'payment_method_id' => 'required|integer|exists:stripe_payment_methods,id',
            'payment_plan_id' => 'required|integer|exists:payment_plans,id',
            'promo_code' => 'string|exists:promo_codes,promo_code|nullable',
            'auto_renew' => 'required|boolean'
        ];
    }
}
