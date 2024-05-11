<?php

namespace App\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;
use App\Mail\SubscriptionAutoRenewalFailure;
use App\Mail\SubscriptionExpiry;
use App\Models\CurrentSubscription;
use App\Models\Notification;
use App\Models\PaymentPlan;
use App\Models\StripeCustomer;
use App\Models\StripePaymentIntent;
use App\Models\StripePaymentMethod;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPaymentAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriptionAutoRenewal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;
    public $action;

    /**
     * Create a new job instance.
     */
    public function __construct($current_id, $action)
    {
        $this->id = $current_id;
        $this->action = $action;
    }

    public function expired(){
        $current = CurrentSubscription::find($this->id);
        $current->status = 2;
        $current->save();

        Notification::create([
            'user_id' => $current->user_id,
            'title' => 'Expired Subscription',
            'body' => "Looks like your subscription has expired. Subscribe now to continue enjoying unrestricted access to all your favourite features. We're here to help you get back on track!",
            'model' => 'subcription',
            'read' => 0,
            'status' => 1
        ]);

        $user = User::find($current->user_id);
        Mail::to($user)->send(new SubscriptionExpiry($user->name));
    }

    public function renew(){
        $current = CurrentSubscription::find($this->id);
        $user = User::find($current->user_id);

        $sub = new SubscriptionController();
        $payment_plan = PaymentPlan::find($current->payment_plan_id);

        $amount_array = $sub->calculate_total_payment($payment_plan->id, $user->id, "subscription_renewal", "");

        $charged = false;
        $errors = "";
        $methods = StripePaymentMethod::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        if(!empty($methods)){
            $stripe = new StripeController();
            foreach($methods as $method){
                if($charge = $stripe->charge_payment_method($method->stripe_customer_id, $method->payment_id,  $amount_array['calculated_amount'])){
                    if($charge->status == 'succeeded'){
                        $charged = true;
                        break;
                    } else {
                        $errors = 'Failed to Charge Payment Method';
                    }
                } else {
                    $errors = $stripe->errors;
                }
            }
        }
        if(!$charged){
            Mail::to($user)->send(new SubscriptionAutoRenewalFailure($user->name, $errors));
            $message = "Failure";
        } else {
            $internal_ref = 'SUB_'.Str::random(20).time();
            $intent = StripePaymentIntent::create([
                'internal_ref' => $internal_ref,
                'user_id' => $user->id,
                'client_secret' => $charge->client_secret,
                'intent_id' => $charge->id,
                'intent_data' => json_encode($charge),
                'amount' => $amount_array['calculated_amount'],
                'purpose' => 'subscription_renewal',
                'purpose_id' => $payment_plan->id,
                'auto_renew' => true,
                'value_given' => 0
            ]);

            $attempt = SubscriptionPaymentAttempt::create([
                'user_id' => $user->id,
                'internal_ref' => $internal_ref,
                'payment_plan_id' => $payment_plan->id,
                'subscription_amount' => $amount_array['original_amount'],
                'amount_paid' => $amount_array['calculated_amount'],
                'promo_percentage' => isset($amount_array['package_promo_percent']) ? $amount_array['package_promo_percent'] : 0,
                'promo_code_id' => isset($used_promo_id) ? $used_promo_id : null,
                'promo_code' => isset($amount_array['promo_code']) ? $amount_array['promo_code'] : null,
                'promo_code_percentage' => isset($amount_array['promo_code_percent']) ? $amount_array['promo_code_percent'] : 0,
                'status' => 0
            ]);

            if($sub->subscribe($user->id, $payment_plan->subscription_package_id, $payment_plan->id, $attempt->amount_paid, $attempt->promo_code_id, 1, 'renew_subscription')){
                $history = SubscriptionHistory::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
                $history->update([
                    'subscription_amount' => $attempt->subscription_amount,
                    'promo_percentage' => $attempt->promo_percentage,
                    'promo_code' => $attempt->promo_code,
                    'promo_code_percentage' => $attempt->promo_code_percentage
                ]);

                $attempt->status = 1;
                $attempt->save();

                $intent->value_given = 1;
                $intent->save();

                Controller::log_activity($user->id, "complete_subscription", "subscription_payment_attempts", $attempt->id);
                $message = "Success";
            } else {
                $errors = $sub->errors;
                $message = "Failure";
                Mail::to($user)->send(new SubscriptionAutoRenewalFailure($user->name, $sub->errors));
            }
        }

        $title = ($message == "Failure") ? "Subscription Auto-Renewal Failed" : "Subscription Auto-Renewed Successfully";
        $body = ($message == "Failure") ? "Oops! We’ve encountered a problem with the auto-renewal of your Psych Insights Subscription for this reason: \"{$errors}\"" : "Great news! Your subscription has been renewed successfully. Continue to enjoy unrestricted access to all the resources and features on the platform. Thank you for staying with us";

        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'model' => 'subscription',
            'read' => 0,
            'status' => 1
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->action == "expired"){
            $this->expired();
        } elseif($this->action == "renew"){
            $this->renew();
        }
    }
}
