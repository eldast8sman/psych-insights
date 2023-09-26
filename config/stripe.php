<?php

    return [
        'api_keys' => [
            'secret_key' => (env('STRIPE_ENV') == 'DEVELOPMENT') ? env('STRIPE_SECRET_KEY_DEV', null) : env('STRIPE_SECRET_KEY_PROD'),
            'public_key' => (env('STRIPE_ENV') == 'DEVELOPMENT') ? env('STRIPE_PUBLIC_KEY_DEV', null) : env('STRIPE_PUBLIC_KEY_PROD')
        ]
    ];