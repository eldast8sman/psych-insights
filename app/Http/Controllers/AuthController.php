<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentPlan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StripeCustomer;
use App\Mail\ForgotPasswordMail;
use App\Models\GoogleLoginToken;
use App\Http\Requests\LoginRequest;
use App\Mail\EmailVerificationMail;
use App\Models\CurrentSubscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\QuestionAnswerSummary;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;

class AuthController extends Controller
{
    private $errors;

    public function store(StoreUserRequest $request){
        if(!$request->terms){
            return response([
                'status' => 'failed',
                'message' => 'You CANNOT signup if you do not accept our Privacy Policy and Terms of Use'
            ], 409);
        }
        $all = $request->only(['name', 'email']);
        $all['password'] = Hash::make($request->password);

        if(!$user = User::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Signup failed! Please try again later'
            ], 409);
        }

        $token = Str::random(10);

        $user->verification_token = $token;
        $user->verification_token_expiry = date('Y-m-d H:i:s', time() + (60 * 10));
        $user->email_verified = 0;
        $user->save();

        $stripe = new StripeController();
        if($customer = $stripe->create_customer($user->name, $user->email)){
            StripeCustomer::create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'customer_data' => json_encode($customer)
            ]);
        }

        $names = explode(' ', $user->name);
        $first_name = $names[0];
        Mail::to($user)->send(new EmailVerificationMail($first_name, $token));

        if(!$login = $this->user_login($request->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $free_trial = SubscriptionPackage::where('free_trial', 1)->first();
        if(!empty($free_trial)){
            $plan = PaymentPlan::where('subscription_package_id', $free_trial->id)->first();
            $sub = new SubscriptionController();
            $sub->subscribe($user->id, $free_trial->id, $plan->id, 0);
        }

        $user = self::user_details($user);
        $user->authorization = $login;

        self::log_activity($user->id, "signup");

        return response([
            'status' => 'success',
            'message' => 'Signup successful',
            'data' => $user
        ], 200);
    }

    public function verify_email(VerifyEmailRequest $request){
        $user = User::find(self::user()->id);
        if($user->email_verified == 1){
            return response([
                'status' => 'failed',
                'message' => 'Email already Verified'
            ], 409);
        }
        if($user->verification_token_expiry < date('Y-m-d H:i:s')){
            $user->verification_token = null;
            $user->verification_token_expiry = date('Y-m-d H:i:s', 0);
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Expired Token'
            ], 409);
        }

        if($user->verification_token != $request->token){
            $user->verification_token = null;
            $user->verification_token_expiry = date('Y-m-d H:i:s', 0);
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Wrong Token'
            ], 409);   
        }

        $user->verification_token = null;
        $user->verification_token_expiry = date('Y-m-d H:i:s', 0);
        $user->email_verified = 1;
        $user->save();

        self::log_activity($user->id, 'activate_email');

        return response([
            'status' => 'success',
            'message' => 'Email Verified Successfully'
        ], 200);
    }

    public function resend_email_verification_link(){
        $user = User::find(self::user()->id);
        if($user->email_verified == 1){
            return response([
                'status' => 'failed',
                'message' => 'Email already Verified'
            ], 409);
        }

        $token = Str::random(10);

        $user->verification_token = $token;
        $user->verification_token_expiry = date('Y-m-d H:i:s', time() + (60 * 10));
        $user->save();

        $names = explode(' ', $user->name);
        $first_name = $names[0];
        Mail::to($user)->send(new EmailVerificationMail($first_name, $token));

        return response([
            'status' => 'success',
            'message' => 'Email Verification Token sent to '.$user->email
        ], 200);
    }

    private function user_login($email, $password){
        $user = User::where('email', $email)->first();
        if(empty($user)){
            $this->errors = "Wrong Credentials";
            return false;
        }
        if($user->status != 1){
            $this->errors = 'User Account has been deactivated';
            return false;
        }
        if(!$token = auth('user-api')->attempt([
            'email' => $email,
            'password' => $password
        ])){
            $this->errors = "Wrong Credentials";
            return false;
        }

        $user->prev_login = !empty($user->last_login) ? $user->last_login : date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        $auth = [
            'token' => $token,
            'type' => 'Bearer',
            'expiry' => auth('user-api')->factory()->getTTL() * 60
        ];

        return $auth;
    }

    public static function user(){
        return auth('user-api')->user();
    }

    public static function user_details(User $user) : User
    {
        $current_subscription = CurrentSubscription::where('user_id', $user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(!empty($current_subscription)){
            $user->current_subscription = $current_subscription;
           
            $user->subscription_package = SubscriptionPackage::where('id', $current_subscription->subscription_package_id)->first(['package', 'podcast_limit', 'article_limit', 'video_limit', 'book_limit', 'free_trial']);
             if($user->subscription_package->free_trial != 1){
                $user->question_type = "Dass21 Questions";
            } else {
                $user->question_type = "Basic Questions";
            }
        } else {
            $user->current_subscription = [];
            $user->question_type = "Basic Questions";
            $user->subscription_package = SubscriptionPackage::where('free_package', 1)->first(['package', 'podcast_limit', 'article_limit', 'audio_limit', 'video_limit', 'book_limit']);
        }

        $last_answer = QuestionAnswerSummary::where('user_id', $user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        $user->next_question_date = !empty($last_answet) ? $last_answer->next_question : date('Y-m-d');

        return $user;
    }

    public function login(LoginRequest $request){
        if(!$auth = $this->user_login($request->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        $user = User::where('email', $request->email)->first();
        $user = self::user_details($user);
        $user->authorization = $auth;

        self::log_activity($user->id, "login");

        return response([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $user
        ], 200);
    }

    public function me(){
        $user = self::user();
        $user = self::user_details($user);

        return response([
            'status' => 'success',
            'message' => 'User fetched successfully',
            'data' => $user
        ], 200);
    }

    public function forgot_password(ForgotPasswordRequest $request){
        $user = User::where('email', $request->email)->first();
        $user->token = Str::random(10);
        $user->token_expiry = date('Y-m-d H:i:s', time() + (60 * 10));
        $user->save();

        Mail::to($user)->send(new ForgotPasswordMail($user->name, $user->token));

        self::log_activity($user->id, "forgot_password");

        return response([
            'status' => 'success',
            'message' => 'Password reset link sent to '.$request->email
        ], 200);
    }

    public function reset_password(ResetPasswordRequest $request){
        $user = User::where('token', $request->token)->first();
        if($user->token_expiry < date('Y-m-d H:i:s')){
            $user->token = null;
            $user->token_expiry = null;
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Password Reset Link has expired'
            ], 409);
        }
        $user->password = Hash::make($request->password);
        $user->token = null;
        $user->token_expiry = null;
        $user->save();

        self::log_activity($user->id, "reset_password");

        return response([
            'status' => 'success',
            'message' => 'Password Reset was successful'
        ], 200);
    }

    public function initiate_google_login(){
        $token = GoogleLoginToken::create([
            'token' => Str::random(30).time(),
            'expiry' => date('Y-m-d H:i:s', time() + (60 * 10))
        ]);

        return response([
            'status' => 'success',
            'message' => 'Login Token generated',
            'data' => ['token' => $token->token]
        ], 200);
    }

    public function google_login(GoogleLoginRequest $request){
        $token = GoogleLoginToken::where('token', $request->token)->first();
        if(empty($token)){
            return response([
                'status' => 'failed',
                'message' => 'Incorrect Credentials'
            ], 409);
        }
        $token->delete();
        if($token->expiry < date('Y-m-d H:i:s')){
            return response([
                'status' => 'failed',
                'message' => 'Expired Link'
            ], 409);
        }
        $user = User::where('email', $request->email)->first();
        if(empty($user)){
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(30)),
                'email_verified' => 1
            ]);
        }
        $token = auth('user-api')->login($user);
        $user->prev_login = !empty($user->last_login) ? $user->last_login : date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        $stripe = new StripeController();
        if($customer = $stripe->create_customer($user->name, $user->email)){
            StripeCustomer::create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'customer_data' => json_encode($customer)
            ]);
        }

        $auth = [
            'token' => $token,
            'type' => 'Bearer',
            'expiry' => auth('user-api')->factory()->getTTL() * 60
        ];

        $user = self::user_details($user);
        $user->authorization = $auth;

        self::log_activity($user->id, "google_login");

        return response([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $user
        ], 200);
    }
}
