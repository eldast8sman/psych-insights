<?php

namespace App\Http\Controllers;

use App\Models\CurrentSubscription;
use App\Models\PaymentPlan;
use App\Models\PromoCode;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public static function subscribe($user_id, $package_id, $plan_id, $amount_paid, $promo_code=null){
        $plan = PaymentPlan::find($plan_id);
        if(empty($plan) or $plan->subscription_package_id != $package_id){
            return false;
        }
        $package = SubscriptionPackage::find($package_id);
        if(empty($package)){
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

        $time = time();
        $start_date = date('Y-m-d');

        if($plan->duration_type == 'week'){
            $end_date = date('Y-m-d', $time + (60 * 60 * 24 * 7 * $plan->duration));
        } elseif($plan->duration_type == 'day'){
            $end_date = date('Y-m-d', $time + (60 * 60 * 24 * $plan->duration));
        } elseif($plan->duration_type == 'year'){
            $end_date = date('Y-m-d', $time + (60 * 60 * 24 * 365 * $plan->duration));
        } elseif($plan->duration_type == 'month'){
            $year = date('Y', $time);
            $month = date('m', $time);
            $date = date('d', $time);

            $new_month = intval($month) + $plan->duration;
            if($new_month > 12){
                $new_month = $new_month - 12;
                $year = strval(intval($year) + 1);
            }
            if(strlen(strval($new_month)) < 2){
                $new_month = '0'.$new_month;
            }
            $new_month = strval($new_month);

            if(($new_month == 4) or ($new_month == 6) or ($new_month == 9) or ($new_month == 11)){
                if($date > 30){
                    $date = strval(30);
                }
            } elseif($new_month == 2){
                if(($year % 4) == 0){
                    if($date > 29){
                        $date = strval(29);
                    }
                } elseif($date > 28){
                    $date = strval(28);
                }
            }

            $end_date = $year.'-'.$new_month.'-'.$date;
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
            'end_date' => $end_date
        ]);

        $current = CurrentSubscription::where('user_id', $user_id)->first();
        $data = [
            'user_id' => $user_id,
            'subscription_package_id' => $package->id,
            'payment_plan_id' => $plan->id,
            'amount_paid' => $amount_paid,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        if(empty($current)){
            CurrentSubscription::create($data);
        } else {
            $current->update($data);
        }
    }
}
