<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateSubscriptionAmountRequest;
use App\Http\Requests\CompleteApplePaymentRequest;
use App\Http\Requests\CompleteSubscriptionPaymentRequest;
use App\Http\Requests\InitiateSubscriptionRequest;
use App\Http\Requests\OldCardSubscriptionRequest;
use App\Jobs\SubscriptionAutoRenewal;
use App\Mail\FreeTrialSubscription;
use App\Mail\SubscriptionSuccessMail;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\NotificationSetting;
use App\Models\ApplePayNotification;
use App\Models\ApplePayToken;
use App\Models\CurrentSubscription;
use App\Models\PaymentPlan;
use App\Models\PromoCode;
use App\Models\QuestionAnswerSummary;
use App\Models\SentMail;
use App\Models\StripeCustomer;
use App\Models\StripePaymentIntent;
use App\Models\StripePaymentMethod;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPaymentAttempt;
use App\Models\UsedPromoCode;
use App\Models\User;
use App\Services\AppleAPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    private $user;
    private $time;

    public $errors = "";

    public function __construct()
    {
        $this->user = AuthController::user();
        if(empty($this->user->last_timezone)){
            $this->time = Carbon::now();
        } else {
            $this->time = Carbon::now($this->user->last_timezone);
        }
    }

    public function subscribe($user_id, $package_id, $plan_id, $amount_paid, $promo_code=null, $auto_renew=0, $type="subscribe"){
        $this->user = User::find($user_id);
        if(empty($this->user->last_timezone)){
            $this->time = Carbon::now();
        } else {
            $this->time = Carbon::now($this->user->last_timezone);
        }
        $plan = PaymentPlan::find($plan_id);
        if(empty($plan) or $plan->subscription_package_id != $package_id){
            $this->errors = "No Payment Plan";
            return false;
        }
        $package = SubscriptionPackage::find($package_id);
        if(empty($package)){
            $this->errors = "No Subscription Package";
            return false;
        }

       
        $histories = SubscriptionHistory::where('user_id', $user_id)->where('subscription_package_id', $package->id);
        if($histories->count() > 0){
            $bonanza = $package->subsequent_promo;
        } else {
            $bonanza = $package->first_time_promo;
        }
        
        $promo_percent = 0;
        if(!empty($promo_code)){
            $promo = PromoCode::find($promo_code);
            if(!empty($promo)){
                $promo_percent = $promo->percentage_off;
            }
        }

        $time = $this->time;
        $start_date = $time->format('Y-m-d');
        $free_trial_mail = false;
        $history = CurrentSubscription::where('user_id', $user_id)->first();
        if(isset($history) and ($history->end_date > $start_date)){
            $time = Carbon::createFromFormat('Y-m-d', $history->end_date, $this->user->last_timezone)->addDay();
            $start_date = $time->format('Y-m-d');

            $cur_package = SubscriptionPackage::find($history->subscription_package_id);
            if($cur_package->free_trial == 1){
                $free_trial_mail = true;
            }
        } 
        
        if($plan->duration_type == 'week'){
            $end_time = $time->addWeeks($plan->duration);
        } elseif($plan->duration_type == 'day'){
            $end_time = $time->addDays($plan->duration);
        } elseif($plan->duration_type == 'year'){
            $end_time = $time->addYears($plan->duration);
        } elseif($plan->duration_type == 'month'){
            $end_time = $time->addMonths($plan->duration);
        }

        $end_date = $end_time->format('Y-m-d');
        
        if($auto_renew == 1){
            $grace_time = $end_time->addDays(2);
            $grace_end = $grace_time->format('Y-m-d');
        } else {
            $grace_end = $end_date;
        }
        $subscription = SubscriptionHistory::create([
            'user_id' => $user_id,
            'subscription_package_id' => $package->id,
            'payment_plan_id' => $plan_id,
            'subscription_amount' => $plan->amount,
            'amount_paid' => $amount_paid,
            'promo_percentage' => $bonanza,
            'promo_code_id' => $promo_code,
            'promo_code' => (isset($promo) and !empty($promo)) ? $promo->promo_code : null,
            'promo_code_percentage' => $promo_percent,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'grace_end' => $grace_end,
            'auto_renew' => $auto_renew,
            'status' => 1
        ]);

        $current = CurrentSubscription::where('user_id', $user_id)->first();
        $next_date =  false;
        if(empty($current)){
            $next_date = true;
        } else {
            if(($current->end_date < Carbon::now($this->user->last_timezone)->format('Y-m-d')) and ($type == 'subscribe')){
                $next_date = true;
            }
        }
        if($type == "subscribe"){
            $data = [
                'user_id' => $user_id,
                'subscription_package_id' => $package->id,
                'payment_plan_id' => $plan->id,
                'amount_paid' => $amount_paid,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'auto_renew' => $auto_renew,
                'grace_end' => $grace_end,
                'status' => 1
            ];
            if(empty($current)){
                $current = CurrentSubscription::create($data);
            } else {
                $current->update($data);
            }

            $answer_summary = QuestionAnswerSummary::where('user_id', $user_id)->orderBy('created_at', 'desc')->first();
            if(!empty($answer_summary)){
                $today = Carbon::now($this->user->last_timezone)->format('Y-m-d');

                if($next_date){
                    $answer_summary->next_question = $today;
                    $answer_summary->save();
                    $user = User::find($user_id);
                    $user->next_assessment = $today;
                    $user->save();
                }
            }
        } elseif($type == "renew_subscription"){
            $current->update([
                'payment_plan_id' => $plan->id,
                'subscription_package_id' => $package->id,
                'amount_paid' => $amount_paid,
                'end_date' => $end_date,
                'auto_renew' => $auto_renew,
                'grace_end' => $grace_end
            ]);
        }

        $user = User::find($user_id);
        if($type == 'subscribe'){
            $not_settings = NotificationSetting::where('new_subscriber_notification', 1);
            if($not_settings->count() > 0){
                foreach($not_settings->get() as $setting){
                    AdminNotification::create([
                        'admin_id' => $setting->admin_id,
                        'title' => 'New Subscriber',
                        'body' => $user->name.' just did a Subscription',
                        'page' => 'subscribers',
                        'identifier' => $current->id,
                        'opened' => 0,
                        'status' => 1
                    ]);
                }
            }
        } elseif($type == 'renew_subscription'){
            $not_settings = NotificationSetting::where('subscription_renewal_notification', 1);
            if($not_settings->count() > 0){
                foreach($not_settings->get() as $setting){
                    AdminNotification::create([
                        'admin_id' => $setting->admin_id,
                        'title' => 'Subscription Renewal',
                        'body' => $user->name.' just renewed their Subscription',
                        'page' => 'subscribers',
                        'identifier' => $current->id,
                        'opened' => 0,
                        'status' => 1
                    ]);
                }
            }
        }

        if($package->free_trial != 1){
            $names = explode(' ', $user->name);
            $firstname = $names[0];
            if($free_trial_mail){
                Mail::to($user)->send(new FreeTrialSubscription($firstname));
                SentMail::create([
                    'recipient_id' => $user->id,
                    'mail_class' => 'FreeTrialSubscription'
                ]);
            } else {
                Mail::to($user)->send(new SubscriptionSuccessMail($firstname));
                SentMail::create([
                    'recipient_id' => $user->id,
                    'mail_class' => 'SubscriptionSuccessMail'
                ]);
            }
        }

        return true;
    }

    public function subscription_packages(){
        $packages = SubscriptionPackage::where('free_trial', 0)->where('free_package', 0)->orderBy('level', 'asc');
        if($packages->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Package was fetched',
                'data' => null
            ], 200);
        }

        $packages = $packages->get(['package', 'slug', 'podcast_limit', 'article_limit', 'audio_limit', 'video_limit', 'book_limit', 'listen_and_learn_limit', 'read_and_reflect_limit', 'learn_and_do_limit', 'first_time_promo', 'subsequent_promo', 'id']);
        foreach($packages as $package){
            $package->payment_plans = PaymentPlan::where('subscription_package_id', $package->id)->orderBy('amount', 'asc')->get(['id', 'amount', 'duration_type', 'duration']);
            unset($package->id);
        }

        return response([
            'status' => 'failed',
            'message' => 'Suscription Packages fetched successfully',
            'data' => $packages 
        ], 200);
    }

    public function fetch_promo_code($promo_code){
        $promo_code = PromoCode::where('promo_code', $promo_code)->first();
        if(empty($promo_code)){
            return response([
                'status' => 'failed',
                'message' => 'No Promo Code was fetched',
                'data' => null
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Promo Code fetched successfully',
            'data' => $promo_code
        ], 200);
    }

    public function calculate_total_payment($id, $user_id, $type='subscription', $promo_code=''){
        $data = [];
        $plan = PaymentPlan::find($id);
        $package = SubscriptionPackage::find($plan->subscription_package_id);

        $amount = $plan->amount;

        $data['original_amount'] = $amount;
        if($type == 'subscription'){
            $previous_sub = SubscriptionHistory::where('user_id', $user_id)->where('subscription_package_id', $package->id)->first();
            if(empty($previous_sub)){
                $percentage = $package->first_time_promo;
            } else {
                $percentage = $package->subsequent_promo;
            }

            if($percentage > 0){
                $data['package_promo_percent'] = $percentage;
                $promo_price = ($percentage / 100) * $amount;
                $data['package_promo_price'] = $promo_price;
                $amount -= $promo_price;
            }

            if(!empty($promo_code)){
                $promo_code = PromoCode::where('promo_code', $promo_code)->first();
                if(!empty($promo_code)){
                    $total_used = UsedPromoCode::where('promo_code_id', $promo_code->id)->get();
                    $total_usage = !empty($total_used) ? $total_used->sum('frequency') : 0;
                    $used = UsedPromoCode::where('user_id', $user_id)->where('promo_code_id', $promo_code->id)->first();
                    $usage = !empty($used) ? $used->frequency : 0;

                    if(($promo_code->total_limit == -1) or ($total_usage < $promo_code->total_limit)){
                        $scope = explode(',', $promo_code->scope);
                        if((($promo_code->usage_limit == -1) or ($promo_code->usage_limit > $usage)) and (in_array($type, $scope) or in_array('all', $scope))){
                            $data['promo_code'] = $promo_code->promo_code;
                            $data['promo_code_percent'] = $promo_code->percentage_off;
                            $price_off = ($promo_code->percentage_off / 100) * $amount;
                            $data['promo_code_price'] = $price_off;
                            $amount -= $price_off;
                        }
                    }
                }
            }
        } elseif($type == 'subscription_renewal'){
            $percentage = $package->subsequent_promo;

            if($percentage > 0){
                $data['package_promo_percent'] = $percentage;
                $promo_price = ($percentage / 100) * $amount;
                $data['package_promo_price'] = $promo_price;
                $amount -= $promo_price;
            }

            if(!empty($promo_code)){
                $promo_code = PromoCode::where('promo_code', $promo_code)->first();
                if(!empty($promo_code)){
                    $total_used = UsedPromoCode::where('promo_code_id', $promo_code->id)->get();
                    $total_usage = !empty($total_used) ? $total_used->sum('frequency') : 0;
                    $used = UsedPromoCode::where('user_id', $user_id)->where('promo_code_id', $promo_code->id)->first();
                    $usage = !empty($used) ? $used->frequency : 0;

                    if(($promo_code->total_limit == -1) or ($total_usage < $promo_code->total_limit)){
                        $scope = explode(',', $promo_code->scope);
                        if((($promo_code->usage_limit == -1) or ($promo_code->usage_limit > $usage)) and (in_array($type, $scope) or in_array('all', $scope))){
                            $data['promo_code'] = $promo_code->promo_code;
                            $data['promo_code_percent'] = $promo_code->percentage_off;
                            $price_off = ($promo_code->percentage_off / 100) * $amount;
                            $data['promo_code_price'] = $price_off;
                            $amount -= $price_off;
                        }
                    }
                }
            }
        }
        $data['calculated_amount'] = $amount;

        return $data;
    }

    public function fetch_calculated_amount(CalculateSubscriptionAmountRequest $request){
        return response([
            'status' => 'success',
            'message' => 'Subscription Amount calculated successfully',
            'data' => $this->calculate_total_payment($request->payment_plan_id, $this->user->id, 'subscription', !empty($request->promo_code) ? $request->promo_code : "")
        ], 200);
    }

    public function fetch_user_payment_methods(){
        $customer = StripeCustomer::where('user_id', $this->user->id)->first();
        if(empty($customer)){
            $stripe = new StripeController();
            $s_customer = $stripe->create_customer($this->user->name, $this->user->email);

            $customer = StripeCustomer::create([
                'user_id' => $this->user->id,
                'customer_id' => $s_customer->id,
                'customer_data' => json_encode($s_customer)
            ]);
        }

        $payment_methods = StripePaymentMethod::where('user_id', $this->user->id)->where('stripe_customer_id', $customer->customer_id);
        if($payment_methods->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Method has been fetched',
                'data' => null
            ], 200);
        }

        $payment_methods = $payment_methods->get(['id', 'payment_method_data']);
        foreach($payment_methods as $method){
            $method->payment_method_data = json_decode($method->payment_method_data);
        }

        return response([
            'status' => 'success',
            'message' => 'Customer Payment Methods fetched successfully',
            'data' => $payment_methods
        ], 200);
    }

    public function remove_payment_method(StripePaymentMethod $method){
        if($method->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Method was fetched'
            ], 404);
        }

        if(!StripeController::detach_payment_method($method->payment_id)){
            return response([
                'status' => 'failed',
                'message' => 'Could not remove Payment Option from Payment Provider'
            ], 500);
        }

        $method->delete();

        return response([
            'status' => 'success',
            'message' => 'Payment Method successfully removed'
        ], 200);
    }

    public function initiate_subscription(InitiateSubscriptionRequest $request){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->end_date > $this->time->format('Y-m-d')){
                $pack = SubscriptionPackage::find($current_plan->subscription_package_id);
                if($pack->free_trial != 1){
                    return response([
                        'status' => 'failed',
                        'message' => 'You still have an active Subscription'
                    ], 409);
                }
            }
        }

        $payment_plan = PaymentPlan::find($request->payment_plan_id);
        if(empty($payment_plan)){
            return response([
                'status' => 'failed',
                'message' => 'Payment Plan Not Provided'
            ], 404);
        }

        $package = SubscriptionPackage::find($payment_plan->subscription_package_id);
        if(empty($package)){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Package was fetched'
            ], 404);
        }

        $promo_code = !empty($request->promo_code) ? (string)$request->promo_code : "";

        $amount_array = $this->calculate_total_payment($payment_plan->id, $this->user->id, "subscription", $promo_code);

        $stripe = new StripeController();
        $customer = StripeCustomer::where('user_id', $this->user->id);
        if($customer->count() < 1){
            $s_customer = $stripe->create_customer($this->user->name, $this->user->email);
            $customer = StripeCustomer::create([
                'user_id' => $this->user->id,
                'customer_id' => $s_customer->id,
                'customer_data' => json_encode($s_customer)
            ]);
        } else {
            $customer  = $customer->first();
        }
        
        $payment_intent = StripeController::create_payment_intent($customer->customer_id, $amount_array['calculated_amount']);

        $customer_secret = $payment_intent->client_secret;

        $internal_ref = 'SUB_'.Str::random(20).time();
        StripePaymentIntent::create([
            'internal_ref' => $internal_ref,
            'user_id' => $this->user->id,
            'client_secret' => $customer_secret,
            'intent_id' => $payment_intent->id,
            'intent_data' => json_encode($payment_intent),
            'amount' => $amount_array['calculated_amount'],
            'purpose' => 'subscription',
            'purpose_id' => $payment_plan->id,
            'auto_renew' => $request->auto_renew,
            'value_given' => 0
        ]);

        if(isset($amount_array['promo_code'])){
            $used_promo_code = PromoCode::where('promo_code', $amount_array['promo_code'])->first();
            if(!empty($used_promo_code)){
                $used_promo_id = $used_promo_code->id;

                $used = UsedPromoCode::where('user_id', $this->user->id)->where('promo_code_id', $used_promo_id)->first();
                if(!empty($used)){
                    $used->frequency += 1;
                    $used->save();
                } else {
                    UsedPromoCode::create([
                        'user_id' => $this->user->id,
                        'promo_code_id' => $used_promo_id,
                        'frequency' => 1
                    ]);
                }
            }
        }

        $attempt = SubscriptionPaymentAttempt::create([
            'user_id' => $this->user->id,
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

        self::log_activity($this->user->id, "initiate_subscription", "subscription_payment_attempts", $attempt->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription Payment Initiated successfully',
            'data' => [
                'client_secret' => $customer_secret,
                'intent_id' => $internal_ref
            ]
        ], 200);
    }

    public function initiate_subscription_apple_pay(PaymentPlan $payment_plan){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->end_date > $this->time->format('Y-m-d')){
                $pack = SubscriptionPackage::find($current_plan->subscription_package_id);
                if($pack->free_trial != 1){
                    return response([
                        'status' => 'failed',
                        'message' => 'You still have an active Subscription'
                    ], 409);
                }
            }
        }

        $package = SubscriptionPackage::find($payment_plan->subscription_package_id);
        if(empty($package)){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Package was fetched'
            ], 404);
        }

        $token = ApplePayToken::create([
            'user_id' => $this->user->id,
            'payment_plan_id' => $payment_plan->id,
            'token' => Str::random(50).time(),
            'token_expiry' => $this->time->addMinutes(30)->format('Y-m-d H:i:s'),
            'value_given' => 0,
            'type' => 'subscribe'
        ]);

        return response([
            'status' => 'success',
            'message' => 'Subscription Initiated successfully',
            'data' => [
                'token' => $token->token
            ]
        ]);
    }

    public function initiate_subscription_old_card(OldCardSubscriptionRequest $request){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->end_date > $this->time->format('Y-m-d')){
                return response([
                    'status' => 'failed',
                    'message' => 'You still have an active Subscription'
                ], 409);
            }
        }

        $payment_plan = PaymentPlan::find($request->payment_plan_id);
        if(empty($payment_plan)){
            return response([
                'status' => 'failed',
                'message' => 'Payment Plan Not Provided'
            ], 404);
        }

        $package = SubscriptionPackage::find($payment_plan->subscription_package_id);
        if(empty($package)){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Package was fetched'
            ], 404);
        }
        $promo_code = !empty($request->promo_code) ? (string)$request->promo_code : "";

        $amount_array = $this->calculate_total_payment($payment_plan->id, $this->user->id, "subscription", $promo_code);

        $method = StripePaymentMethod::find($request->payment_method_id);
        if($method->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Method was fetched'
            ], 404);
        }

        $stripe = new StripeController();
        if(!$charge = $stripe->charge_payment_method($method->stripe_customer_id, $method->payment_id, $amount_array['calculated_amount'])){
            return response([
                'status' => 'failed',
                'message' => $stripe->errors
            ], 409);
        }
        if($charge->status != 'succeeded'){
            return response([
                'status' => 'failed',
                'message' => 'Failed to Charge Payment Method'
            ], 409);
        }

        $internal_ref = 'SUB_'.Str::random(20).time();
        $intent = StripePaymentIntent::create([
            'internal_ref' => $internal_ref,
            'user_id' => $this->user->id,
            'client_secret' => $charge->client_secret,
            'intent_id' => $charge->id,
            'intent_data' => json_encode($charge),
            'amount' => $amount_array['calculated_amount'],
            'purpose' => 'subscription',
            'purpose_id' => $payment_plan->id,
            'auto_renew' => $request->auto_renew,
            'value_given' => 0
        ]);

        if(isset($amount_array['promo_code'])){
            $used_promo_code = PromoCode::where('promo_code', $amount_array['promo_code'])->first();
            if(!empty($used_promo_code)){
                $used_promo_id = $used_promo_code->id;

                $used = UsedPromoCode::where('user_id', $this->user->id)->where('promo_code_id', $used_promo_id)->first();
                if(!empty($used)){
                    $used->frequency += 1;
                    $used->save();
                } else {
                    UsedPromoCode::create([
                        'user_id' => $this->user->id,
                        'promo_code_id' => $used_promo_id,
                        'frequency' => 1
                    ]);
                }
            }
        }

        $attempt = SubscriptionPaymentAttempt::create([
            'user_id' => $this->user->id,
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

        if(!$this->subscribe($this->user->id, $package->id, $payment_plan->id, $attempt->amount_paid, $attempt->promo_code_id, $request->auto_renew)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $history = SubscriptionHistory::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->first();
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

        $user = AuthController::user_details($this->user);

        self::log_activity($this->user->id, "complete_subscription", "subscription_payment_attempts", $attempt->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription successful',
            'data' => $user
        ], 200);
    }

    public function initiate_subscription_renewal(InitiateSubscriptionRequest $request){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->grace_end < $this->time->format('Y-m-d')){
                return response([
                    'status' => 'failed',
                    'message' => 'You do not have an Active Subscription to Renew'
                ], 409);
            }
        }

        $package = SubscriptionPackage::find($current_plan->subscription_package_id);
        if($package->free_trial == 1){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew Free Trial'
            ], 409);
        }

        $payment_plan = PaymentPlan::find($request->payment_plan_id);
        if($payment_plan->subscription_package_id != $package->id){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew a Subscription you are not subscribed to'
            ], 409);
        }

        $promo_code = !empty($request->promo_code) ? (string)$request->promo_code : "";

        $amount_array = $this->calculate_total_payment($payment_plan->id, $this->user->id, "subscription_renewal", $promo_code);

        $stripe = new StripeController();
        $customer = StripeCustomer::where('user_id', $this->user->id);
        if($customer->count() < 1){
            $s_customer = $stripe->create_customer($this->user->name, $this->user->email);
            $customer = StripeCustomer::create([
                'user_id' => $this->user->id,
                'customer_id' => $s_customer->id,
                'customer_data' => json_encode($s_customer)
            ]);
        } else {
            $customer  = $customer->first();
        }
        
        $payment_intent = StripeController::create_payment_intent($customer->customer_id, $amount_array['calculated_amount']);

        $customer_secret = $payment_intent->client_secret;

        $internal_ref = 'SUB_'.Str::random(20).time();
        StripePaymentIntent::create([
            'internal_ref' => $internal_ref,
            'user_id' => $this->user->id,
            'client_secret' => $customer_secret,
            'intent_id' => $payment_intent->id,
            'intent_data' => json_encode($payment_intent),
            'amount' => $amount_array['calculated_amount'],
            'purpose' => 'subscription_renewal',
            'purpose_id' => $payment_plan->id,
            'auto_renew' => $request->auto_renew,
            'value_given' => 0
        ]);

        if(isset($amount_array['promo_code'])){
            $used_promo_code = PromoCode::where('promo_code', $amount_array['promo_code'])->first();
            if(!empty($used_promo_code)){
                $used_promo_id = $used_promo_code->id;

                $used = UsedPromoCode::where('user_id', $this->user->id)->where('promo_code_id', $used_promo_id)->first();
                if(!empty($used)){
                    $used->frequency += 1;
                    $used->save();
                } else {
                    UsedPromoCode::create([
                        'user_id' => $this->user->id,
                        'promo_code_id' => $used_promo_id,
                        'frequency' => 1
                    ]);
                }
            }
        }

        $attempt = SubscriptionPaymentAttempt::create([
            'user_id' => $this->user->id,
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

        self::log_activity($this->user->id, "initiate_subscription_renewal", "subscription_payment_attempts", $attempt->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription Payment Initiated successfully',
            'data' => [
                'client_secret' => $customer_secret,
                'intent_id' => $internal_ref
            ]
        ], 200);
    }

    public function initiate_subscription_renewal_old_card(OldCardSubscriptionRequest $request){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->grace_end < $this->time->format('Y-m-d')){
                return response([
                    'status' => 'failed',
                    'message' => 'You do not have an Active Subscription to Renew'
                ], 409);
            }
        }

        $package = SubscriptionPackage::find($current_plan->subscription_package_id);
        if($package->free_trial == 1){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew Free Trial'
            ], 409);
        }

        $payment_plan = PaymentPlan::find($request->payment_plan_id);
        if($payment_plan->subscription_package_id != $package->id){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew a Subscription you are not subscribed to'
            ], 409);
        }

        $promo_code = !empty($request->promo_code) ? (string)$request->promo_code : "";

        $amount_array = $this->calculate_total_payment($payment_plan->id, $this->user->id, "subscription_renewal", $promo_code);

        $method = StripePaymentMethod::find($request->payment_method_id);
        if($method->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Method was fetched'
            ], 404);
        }

        $stripe = new StripeController();
        if(!$charge = $stripe->charge_payment_method($method->stripe_customer_id, $method->payment_id, $amount_array['calculated_amount'])){
            return response([
                'status' => 'failed',
                'message' => $stripe->errors
            ], 409);
        }
        if($charge->status != 'succeeded'){
            return response([
                'status' => 'failed',
                'message' => 'Failed to Charge Payment Method'
            ], 409);
        }

        $internal_ref = 'SUB_'.Str::random(20).time();
        $intent = StripePaymentIntent::create([
            'internal_ref' => $internal_ref,
            'user_id' => $this->user->id,
            'client_secret' => $charge->client_secret,
            'intent_id' => $charge->id,
            'intent_data' => json_encode($charge),
            'amount' => $amount_array['calculated_amount'],
            'purpose' => 'subscription_renewal',
            'purpose_id' => $payment_plan->id,
            'auto_renew' => $request->auto_renew,
            'value_given' => 0
        ]);

        if(isset($amount_array['promo_code'])){
            $used_promo_code = PromoCode::where('promo_code', $amount_array['promo_code'])->first();
            if(!empty($used_promo_code)){
                $used_promo_id = $used_promo_code->id;

                $used = UsedPromoCode::where('user_id', $this->user->id)->where('promo_code_id', $used_promo_id)->first();
                if(!empty($used)){
                    $used->frequency += 1;
                    $used->save();
                } else {
                    UsedPromoCode::create([
                        'user_id' => $this->user->id,
                        'promo_code_id' => $used_promo_id,
                        'frequency' => 1
                    ]);
                }
            }
        }

        $attempt = SubscriptionPaymentAttempt::create([
            'user_id' => $this->user->id,
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

        if(!$this->subscribe($this->user->id, $package->id, $payment_plan->id, $attempt->amount_paid, $attempt->promo_code_id, $request->auto_renew, 'renew_subscription')){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $history = SubscriptionHistory::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->first();
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

        $user = AuthController::user_details($this->user);

        self::log_activity($this->user->id, "complete_subscription", "subscription_payment_attempts", $attempt->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription successful',
            'data' => $user
        ], 200);
    }

    public function initiate_subscription_renewal_apple_pay(PaymentPlan $payment_plan){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(!empty($current_plan)){
            if($current_plan->grace_end < $this->time->format('Y-m-d')){
                return response([
                    'status' => 'failed',
                    'message' => 'You do not have an Active Subscription to Renew'
                ], 409);
            }
        }

        $package = SubscriptionPackage::find($current_plan->subscription_package_id);
        if($package->free_trial == 1){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew Free Trial'
            ], 409);
        }

        if($payment_plan->subscription_package_id != $package->id){
            return response([
                'status' => 'failed',
                'message' => 'You cannot renew a Subscription you are not subscribed to'
            ], 409);
        }

        $token = ApplePayToken::create([
            'user_id' => $this->user->id,
            'payment_plan_id' => $payment_plan->id,
            'token' => Str::random(50).time(),
            'token_expiry' => $this->time->addMinutes(30)->format('Y-m-d H:i:s'),
            'value_given' => 0,
            'type' => 'renew_subscription'
        ]);

        return response([
            'status' => 'success',
            'message' => 'Subscription Initiated successfully',
            'data' => [
                'token' => $token->token
            ]
        ]);
    }

    public function complete_subscription(CompleteSubscriptionPaymentRequest $request){
        $intent = StripePaymentIntent::where('user_id', $this->user->id)->where('internal_ref', $request->intent_ref)->first();
        if(empty($intent)){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Intent fetched'
            ], 404);
        }
        if($intent->value_given == 1){
            return response([
                'status' => 'failed',
                'message' => 'Value already gien for this Payment'
            ], 404);
        }

        $attempt = SubscriptionPaymentAttempt::where('user_id', $this->user->id)->where('internal_ref', $intent->internal_ref)->first();
        if(empty($attempt)){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Attempt fetched'
            ], 404);
        }
        if($attempt->status == 1){
            return response([
                'status' => 'failed',
                'message' => 'Payment already activated'
            ], 409);
        }

        if(!$payment_intent = StripeController::retrieve_payment_intent($intent->intent_id)){
            return response([
                'status' => 'failed',
                'message' => 'Payment cannot be verified'
            ], 404);
        }

        $intent->intent_data = json_encode($payment_intent);
        $intent->save();

        if(!empty($payment_intent->payment_method)){
            $payment_id = $payment_intent->payment_method;
            $found = StripePaymentMethod::where('user_id', $this->user->id)->where('payment_id', $payment_id)->first();
            if(empty($found)){
                $customer = StripeCustomer::where('user_id', $this->user->id)->first();
                $payment_method = StripeController::retrieve_payment_method($payment_id);
                StripePaymentMethod::create([
                    'user_id' => $this->user->id,
                    'stripe_customer_id' => $customer->customer_id,
                    'payment_id' => $payment_id,
                    'payment_method_data' => json_encode($payment_method)
                ]);
            }
        }

        if($payment_intent->status != 'succeeded'){
            return response([
                'status' => 'failed',
                'message' => 'Payment has not yet succeeded'
            ], 409);
        }

        $payment_plan = PaymentPlan::find($intent->purpose_id);
        $package = SubscriptionPackage::find($payment_plan->subscription_package_id);

        if($intent->purpose == "subscription"){
            $type = "subscribe";
        } elseif($intent->purpose == "subscription_renewal"){
            $type = "renew_subscription";
        }

        if(!$this->subscribe($this->user->id, $package->id, $payment_plan->id, $attempt->amount_paid, $attempt->promo_code_id, $intent->auto_renew, $type)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $history = SubscriptionHistory::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->first();
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

        $user = AuthController::user_details($this->user);

        self::log_activity($this->user->id, "complete_subscription", "subscription_payment_attempts", $attempt->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription successful',
            'data' => $user
        ], 200);
    }

    public function complete_subscription_apple_pay(CompleteApplePaymentRequest $request){
        $apple_pay = ApplePayToken::where('user_id', $this->user->id)->where('token', $request->token)->first();
        if(empty($apple_pay)){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Intent fetched'
            ], 404); 
        }
        if($apple_pay->value_given == 1){
            return response([
                'status' => 'failed',
                'message' => 'Value already gien for this Payment'
            ], 404);
        }

        $payment_plan = PaymentPlan::find($apple_pay->payment_plan_id);
        $package = SubscriptionPackage::find($payment_plan->subscription_package_id);

        if(!$this->subscribe($this->user->id, $package->id, $payment_plan->id, $request->amount_paid, null, 0, $apple_pay->type)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $apple_pay->value_given = 1;
        $apple_pay->save();

        $user = AuthController::user_details($this->user);

        self::log_activity($this->user->id, "complete_subscription", "apple_pay_tokens", $apple_pay->id);

        return response([
            'status' => 'success',
            'message' => 'Subscription successful',
            'data' => $user
        ], 200);
    }

    public function subscription_attempts(){
        $filter = isset($_GET['filter']) ? $_GET['filter'] : NULL;
        $from = !empty($_GET['from']) ? (string)$_GET['from'] : "";
        $to = !empty($_GET['to']) ? (string)$_GET['to'] : "";
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : "desc";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $attempts = SubscriptionPaymentAttempt::where('user_id', $this->user->id);
        if($filter !== NULL){
            $attempts = $attempts->where('status', $filter);
        }
        if(!empty($from)){
            $from_time = $from." 00:00:00";
            $attempts = $attempts->where('created_at', '>=', $from_time);
        }
        if(!empty($to)){
            $to_time = $to." 23:59:59";
            $attempts = $attempts->where('created_at', '<=', $to_time);
        }
        if($attempts->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Attempt fetched'
            ], 404);
        }
        $attempts = $attempts->orderBy('created_at', $sort);
        $attempts = $attempts->paginate($limit);

        foreach($attempts as $attempt){
            unset($attempt->id);
            $payment_plan = PaymentPlan::find($attempt->payment_plan_id);
            $attempt->subscription_package = SubscriptionPackage::find($payment_plan->subscription_package_id);
            $attempt->payment_plan = $payment_plan;
        }

        return response([
            'status' => 'success',
            'message' => 'Subscription Attempts fetched successfully',
            'data' => $attempts
        ], 200);
    }

    public function subscription_attempt($internal_ref){
        $attempt = SubscriptionPaymentAttempt::where('user_id', $this->user->id)->where('internal_ref', $internal_ref)->first();
        if(empty($attempt)){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Attempt was fetched'
            ], 404);
        }

        $payment_plan = PaymentPlan::find($attempt->payment_plan_id);
        $attempt->subscription_package = SubscriptionPackage::find($payment_plan->subscription_package_id);
        $attempt->payment_plan = $payment_plan; 
        unset($attempt->id);
        
        return response([
            'status' => 'success',
            'message' => 'Subscription Attempt fetched successfully',
            'data' => $attempt
        ], 200);
    }

    public function charge_previous_card(StripePaymentMethod $method){
        if($method->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Method was fetched'
            ], 404);
        }

        $stripe = new StripeController();
        if($charge = $stripe->charge_payment_method($method->stripe_customer_id, $method->payment_id, 100)){
            return response([
                'status' => 'success',
                'message' => 'Charge successful',
                'data' => $charge
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'Charge failed',
                'data' => $stripe->errors
            ], 405);
        }
    }

    public function applepay_notification(Request $request, $type){
        Log::info('Apple Notification received');
        $notification = ApplePayNotification::create([
            'type' => $type,
            'notification_data' => json_encode($request->all()),
            'user_id' => isset($request->userID) ? $request->userID : null,
            'product_id' => isset($request->productId) ? $request->productId : null
        ]);

        if(isset($request->status)){
            if($request->status == "PurchaseStatus.purchased"){
                $current_plan = CurrentSubscription::where('user_id', intval($request->userID))->first();
                $free_trial = SubscriptionPackage::where('free_trial', 1)->first();
                if(!empty($current_plan) and ($current_plan->grace_end > $this->time->format('Y-m-d')) and ($current_plan->subscription_package_id != $free_trial->id)){
                    $type = "renew_subscription";
                } else {
                    $type = "subscribe";
                }
    
                $intent = StripePaymentIntent::create([
                    'internal_ref' => 'SUB_APL'.Str::random(17).time(),
                    'user_id' => $request->userID,
                    'client_secret' => 'NOT-STRIPE-'.Str::random(10).time(),
                    'intent_id' => 'NOT_STRIPE-'.Str::random(10).time(),
                    'intent_data' => json_encode($request->all()),
                    'amount' => $request->amount,
                    'purpose' => 'subscription',
                    'purpose_id' =>  intval($request->productId),
                    'auto_renew' => false,
                    'value_given' => 0
                ]);
            } else {
                return false;
            }
    
            $plan_id = intval($request->productId);
            $payment_plan = PaymentPlan::find($plan_id);
            $package = SubscriptionPackage::find($payment_plan->subscription_package_id);
    
            if(!$this->subscribe($request->userID, $package->id, $payment_plan->id, $payment_plan->amount, null, 0, $type)){
                return response([
                    'status' => 'failed',
                    'message' => $this->errors
                ], 409);
            }
    
            $intent->value_given = 1;
            $intent->save();
            
            $notification->value_given = 1;
            $notification->save();
        } elseif(isset($request->signedPayload)){
            $array = explode('.', $request->signedPayload);
            if (!isset($array[1])) {
                return response(['status' => 'error', 'message' => 'Invalid payload'], 400);
            }
            $data = json_decode(base64_decode($array[1]));

            $notification->update([
                'notification_type' => $data->notificationType,
                'notification_id' => $data->notificationUUID
            ]);

            Log::info("Stage 1". json_encode($notification));

            $type = strtolower($notification->notification_type);
            if(($type == 'one_time_charge') or ($type == 'subscribed') or ($type == 'renewal') or ($type == 'did_renew')){
                $info_token = $data->data->signedTransactionInfo;

                $array2 = explode('.', $info_token);
                $data2 = json_decode(base64_decode($array2[1]));

                Log::info("Stage 2". json_encode($data2));

                $transaction_id = $data2->transactionId;
                $token = !empty($data2->appAccountToken) ? $data2->appAccountToken : null;

                if (empty($token)) {
                    Log::error("Apple Notification: {$notification->id} did not carry App Account Token");
                    return response(['status' => 'error', 'message' => 'App Account Token missing'], 400);
                }

                if(empty(ApplePayNotification::where('transaction_id', $transaction_id)->where('app_account_token', $token)->where('value_given', 1)->first())){
                    $user = User::where('app_account_token', $token)->first();
                    if(!empty($user)){
                        $notification->update([
                            'product_id' => $data2->productId,
                            'app_account_token' => $data2->appAccountToken,
                            'user_id' => $user->id,
                            'transaction_id' => $data2->transactionId,
                            'original_transaction_id' => $data2->originalTransactionId
                        ]);

                        Log::info("Stage 3". json_encode($notification));

                        $own_type = strtolower($data2->inAppOwnershipType);
                        $reason = strtolower($data2->transactionReason);

                        if(($own_type == "purchased") and (($reason == "purchase") or ($reason == "renewal"))){
                            $plan_id = intval($notification->product_id);
                            $plan = PaymentPlan::find($plan_id);
                            $current = CurrentSubscription::where('user_id', $user->id)->where('subscription_package_id', $plan->subscription_package_id)->where('grace_end', '>=', $this->time->format('Y-m-d'))->first();
                            if(empty($current)){
                                $sub_type = "subscribe";
                            } else {
                                $sub_type = "renew_subscription";
                            }

                            $intent = StripePaymentIntent::create([
                                'internal_ref' => 'SUB_APL'.Str::random(17).time(),
                                'user_id' => $user->id,
                                'client_secret' => 'NOT-STRIPE-'.Str::random(10).time(),
                                'intent_id' => 'NOT_STRIPE-'.Str::random(10).time(),
                                'intent_data' => json_encode($data2),
                                'amount' => $data2->price,
                                'purpose' => 'subscription',
                                'purpose_id' =>  intval($data2->productId),
                                'auto_renew' => false,
                                'value_given' => 0
                            ]);

                            if($this->subscribe($user->id, $plan->subscription_package_id, $plan->id, $plan->amount, null, 0, $sub_type)){
                                $intent->value_given = 1;
                                $intent->save();
                                
                                $notification->value_given = 1;
                                $notification->save();
                            }
                        }
                    }
                } else {
                    $notification->delete();
                }
            }

            return response([
                'status' => 'success',
                'message' => 'Notification Received'
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Apple Notification Done'
        ]);
    }

    public function auto_renew(){
        $current_plan = CurrentSubscription::where('user_id', $this->user->id)->first();
        if(empty($current_plan)){
            return response([
                'status' => 'failed',
                'message' => 'No Active Subscription'
            ], 404);
        }
        if($current_plan->end_date < $this->time->format('Y-m-d')){
            return response([
                'status' => 'failed',
                'message' => 'Subscription has expired'
            ], 409);
        }

        $current_plan->auto_renew = ($current_plan->auto_renew == 1) ? 0 : 1;
        $current_plan->save();
    }
}
