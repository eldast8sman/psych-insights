<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AppleAPIService
{
    private function signToken($payload, $header = null)
    {
        $algorithm = 'ES256';
        $keyfile = Storage::disk('public')->get('/apple/AppleKey.p8');

        $header = array_merge($header, ['alg' => $algorithm]);

        // Encode the payload and header into a JWT
        $jwt = JWT::encode($payload, $keyfile, $algorithm, env('APPLE_KEY_ID'));

        return $jwt;
    }

    public function generate_token()
    {
        $header = [
            "kid" => env('APPLE_KEY_ID'),
            "typ" => "JWT"
        ];
        $payload = [
            "iss" => env('APPLE_ISSUER_ID'),
            "iat" => time(),
            "exp" => time() + (60 * 30),
            "aud" => "appstoreconnect-v1",
            "nonce" => rand(1111111111, 9999999999) . time(),
            "bid" => "com.psychinsight.psychinsights"
        ];

        // Sign and generate the JWT
        $token = $this->signToken($payload, $header);

        return $token;
    }

    public function subscriptionStatus($tranx_id, $env = 'Sandbox')
    {
        $token = $this->generate_token();

        $url = $env == 'Sandbox'
            ? 'https://api.storekit-sandbox.itunes.apple.com/inApps/v1/subscriptions/' . $tranx_id
            : 'https://api.storekit.itunes.apple.com/inApps/v1/subscriptions/' . $tranx_id;

        $response = Http::withToken($token)->get($url);

        if ($response->failed()) {
            return $response->throw()->json();
        }

        $response = json_decode($response->body());
        $enc_renewal = array_shift($response->data)->lastTransactions[0]->signedTransactionInfo;
        $exploded = explode('.', $enc_renewal);
        $payload = $exploded[1];
        return json_decode(base64_decode($payload));
    }
}
