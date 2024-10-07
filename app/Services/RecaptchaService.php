<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function verify($token)
    {
        $secret_key = config('services.recaptcha.secret_key');
        $verify = Http::asForm()->post(config('services.recaptcha.base_url').'/siteverify', [
            'secret' => $secret_key,
            'response' => $token
        ]);

        return $verify->json();
    }
}