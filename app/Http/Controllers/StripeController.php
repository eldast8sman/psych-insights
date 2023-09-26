<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeController extends Controller
{
    public static $stripe;
    public $errors = "";

    public function __construct()
    {
        self::$stripe = new StripeClient(config('stripe.api_keys.secret_key'));
    }

    public function create_customer($name, $email){
        $customer = null;
        try {
            $created = self::$stripe->customers->create([
                'name' => $name,
                'email' => $email
            ]);

            $customer = $created;
        } catch(Exception $e){
            $customer = $this->errors = $e->getMessage();
        }

        return $customer;
    }

    public static function create_payment_intent($customer_id, $amount){
        $amount = $amount * 100;

        $intent = self::$stripe->paymentIntents->create([
            'customer' => $customer_id,
            'setup_future_usage' => 'off_session',
            'amount' => $amount,
            'currency' => 'usd'
        ]);

        return $intent;
    }

    public static function retrieve_payment_intent($intent_id){
        self::$stripe = new StripeClient(config('stripe.api_keys.secret_key'));
        try {
            return self::$stripe->paymentIntents->retrieve($intent_id);
        } catch(ApiErrorException $e) {
            return false;
        }
    }

    public static function retrieve_payment_method($method_id){
        self::$stripe = new StripeClient(config('stripe.api_keys.secret_key'));
        try {
            return self::$stripe->paymentMethods->retrieve($method_id);
        } catch(ApiErrorException $e) {
            return false;
        }
    }

    public static function detach_payment_method($method_id){
        self::$stripe = new StripeClient(config('stripe.api_keys.secret_key'));
        try {
            self::$stripe->paymentMethods->detach($method_id);
            return true;
        } catch(ApiErrorException $e){
            return false;
        }
    }
}
