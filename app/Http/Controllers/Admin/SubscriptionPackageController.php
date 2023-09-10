<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BasicPackageRequest;
use App\Http\Requests\Admin\FreePackageRequest;
use App\Http\Requests\Admin\StoreSubscriptionPackageRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPackageRequest;
use App\Models\PaymentPlan;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use stdClass;

class SubscriptionPackageController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = SubscriptionPackage::where('free_trial', 0)->where('free_package', 0)->orderBy('level', 'asc');
        if($packages->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Subscription Package was fetched',
                'data' => null
            ], 200);
        }

        $packages = $packages->get();
        foreach($packages as $package){
            $package = self::package($package);
        }

        return response([
            'status' => 'success',
            'message' => 'Subscription Packages fetched successfully',
            'data' => $packages
        ], 200);
    }

    public function add_free_package(FreePackageRequest $request){
        $package = SubscriptionPackage::where('free_trial', 1)->first();
        if(empty($package)){
            $package = SubscriptionPackage::create([
                'package' => 'Free Trial',
                'level' => 100,
                'podcast_limit' => -1,
                'article_limit' => -1,
                'audio_limit' => -1,
                'video_limit' => -1,
                'free_trial' => 1,
                'first_time_promo' => 0
            ]);
        }

        $plan = PaymentPlan::where('subscription_package_id', $package->id)->first();
        if(empty($plan)){
            $plan = PaymentPlan::create([
                'subscription_package_id' => $package->id,
                'amount' => 0,
                'duration_type' => $request->duration_type,
                'duration' => $request->duration
            ]);
        } else {
            $plan->duration_type = $request->duration_type;
            $plan->duration = $request->duration;
            $plan->save();
        }

        $free = new stdClass();

        $free->package = "Free Trial";
        $free->duration_type = $plan->duration_type;
        $free->duration = $plan->duration;

        return response([
            'status' => 'success',
            'message' => 'Free Trial created/updated successfully',
            'data' => $free
        ], 200);
    }

    public function fetch_free_package(){
        $package = SubscriptionPackage::where('free_trial', 1)->first();
        if(empty($package)){
            return response([
                'status' => 'failed',
                'message' => 'No Free Trial Package was fetched',
                'data' => null
            ], 200);
        }

        $plan = PaymentPlan::where('subscription_package_id', $package->id)->first();
        if(empty($plan)){
            return response([
                'status' => 'failed',
                'message' => 'No Free Trial Package was found',
                'data' => null
            ], 200);
        }

        $free = new stdClass();

        $free->package = "Free Trial";
        $free->duration_type = $plan->duration_type;
        $free->duration = $plan->duration;

        return response([
            'status' => 'success',
            'message' => 'Free Trial fetched successfully',
            'data' => $free
        ], 200);
    }

    public function destroy_free_package(){
        $packages = SubscriptionPackage::where('free_trial', 1)->get();
        if(!empty($packages)){
            foreach($packages as $package){
                $package->delete();
                $plans = PaymentPlan::where('subscription_package_id', $package->id)->get();
                if(!empty($plans)){
                    foreach($plans as $plan){
                        $plan->delete();
                    }
                }
            }

            return response([
                'status' => 'success',
                'message' => 'Free Trial Package deleted successfully' 
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'No Free Trial Package was fetched'
            ], 404);
        }
    }

    public static function package($package) : SubscriptionPackage
    {
        $package->payment_plans = PaymentPlan::where('subscription_package_id', $package->id)->orderBy('amount', 'asc')->get();
        return $package;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionPackageRequest $request)
    {
        $all = $request->except(['payment_plans']);
        if(!$package = SubscriptionPackage::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Failed to add Subscription Package'
            ], 500);
        }

        foreach($request->payment_plans as $plan){
            PaymentPlan::create([
                'subscription_package_id' => $package->id,
                'amount' => $plan['amount'],
                'duration_type' => $plan['duration_type'],
                'duration' => $plan['duration']
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Subscription Package successfully added',
            'data' => self::package($package)
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(SubscriptionPackage $package)
    {
        return response([
            'status' => 'success',
            'message' => 'Package successfully fetched',
            'data' => self::package($package)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionPackageRequest $request, SubscriptionPackage $package)
    {
        $all = $request->except(['payment_plans']);
        if(!$package->update($all)){
            return response([
                'status' => 'failed',
                'message' => 'Subscription update failed'
            ], 500);
        }

        foreach($request->payment_plans as $plan){
            if(isset($plan['id']) && !empty($plan['id'])){
                $payment_plan = PaymentPlan::find($plan['id']);
                if($payment_plan->subscription_package_id == $package->id){
                    unset($plan['id']);
                    $payment_plan->update($plan);
                }
            } else {
                unset($plan['id']);
                $plan['subscription_package_id'] = $package->id;
                PaymentPlan::create($plan);
            }
        }

        return response([
            'status' => 'failed',
            'message' => 'Subscription Package Updated successfully',
            'data' => self::package($package)
        ], 200);
    }

    public function destroy_payment_plan(PaymentPlan $plan){
        $plan->delete();

        $package = SubscriptionPackage::find($plan->subscription_package_id);

        return response([
            'status' => 'success',
            'message' => 'Payment Plan deleted successfully',
            'data' => self::package($package)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubscriptionPackage $package)
    {
        $package->delete();
        $plans = PaymentPlan::where('subscription_package_id', $package->id);
        if($plans->count() > 0){
            foreach($plans->get() as $plan){
                $plan->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Subscription Package deleted successfully'
        ], 200);
    }

    public function add_basic_package(BasicPackageRequest $request){
        $package = SubscriptionPackage::where('free_package', 1)->first();
        if(empty($package)){
            SubscriptionPackage::create([
                'package' => 'Free Package',
                'level' => 0,
                'podcast_limit' => $request->podcast_limit,
                'article_limit' => $request->article_limit,
                'audio_limit' => $request->audio_limit,
                'video_limit' => $request->video_limit,
                'book_limit' => $request->book_limit,
                'free_trial' => 0,
                'first_time_promo' => 0,
                'subsequent_promo' => 0,
                'free_package' => 1
            ]);
        } else {
            $package->update($request->all());
        }

        $package = SubscriptionPackage::where('free_package', 1)->first(['podcast_limit', 'article_limit', 'audio_limit', 'video_limit']);

        return response([
            'status' => 'success',
            'message' => 'Free Package Settings set up successfully',
            'data' => $package
        ], 200);
    }

    public function fetch_basic_package(){
        $package = SubscriptionPackage::where('free_package', 1)->first(['podcast_limit', 'article_limit', 'audio_limit', 'video_limit']);

        return response([
            'status' => 'success',
            'message' => 'Free Package successfully fetched',
            'data' => $package
        ], 200);
    }
}
