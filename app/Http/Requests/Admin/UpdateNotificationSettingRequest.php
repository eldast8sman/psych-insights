<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingRequest extends FormRequest
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
            'new_user_notification' => 'required|boolean',
            'new_subscriber_notification' => 'required|boolean',
            'subscription_renewal_notification' => 'required|boolean',
            'account_deactivation_notification' => 'required|boolean',
            'prolong_inactivity_notification' => 'required|boolean'
        ];
    }
}
