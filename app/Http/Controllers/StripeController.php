<?php

namespace App\Http\Controllers;

use Exception;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Stripe\Exception\CardException;
use Stripe\Exception\ApiErrorException;

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
        $amount = round($amount, 2) * 100;

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

    public function charge_payment_method($customer_id, $payment_method_id, $amount){
        $amount = round($amount, 2) * 100;

        Stripe::setApiKey(config('stripe.api_keys.secret_key'));

        try {
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
                'customer' => $customer_id,
                'payment_method' => $payment_method_id,
                'return_url' => 'https://127.0.0.1:8000',
                'off_session' => true,
                'confirm' => true,
            ]);

            return $intent;
        } catch (ApiErrorException $e) {
            $this->errors = $e->getMessage();
            return false;
        }
    }
}
