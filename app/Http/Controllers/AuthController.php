<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DailyTip;
use App\Models\DailyQuote;
use App\Models\PaymentPlan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StripeCustomer;
use App\Mail\ForgotPasswordMail;
use App\Models\GoogleLoginToken;
use App\Models\UserDeactivation;
use App\Http\Requests\LoginRequest;
use App\Mail\EmailVerificationMail;
use App\Models\CurrentSubscription;
use App\Models\DailyQuestionAnswer;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\QuestionAnswerSummary;
use Illuminate\Support\Facades\Crypt;
use App\Models\EmailVerificationToken;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ChangeNameRequest;
use App\Http\Requests\ChangeEmailRequest;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ContactUsRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\DeactivateAccountRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Mail\ContactUsMail;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\NotificationSetting;
use App\Models\UserNotificationSetting;
use Exception;

class AuthController extends Controller
{
    private $errors;
    private $file_disk = 's3';

    public function store(StoreUserRequest $request){
        if(!$request->terms){
            return response([
                'status' => 'failed',
                'message' => 'You CANNOT signup if you do not accept our Privacy Policy and Terms of Use'
            ], 409);
        }
        $all = $request->only(['name', 'email']);
        $all['password'] = Hash::make($request->password);

        $founds = User::where('deactivated', 0)->where('email', $all['email']);
        if($founds->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'This Email is already taken'
            ], 409);
        }
        if(!$user = User::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Signup failed! Please try again later'
            ], 409);
        }

        $token = mt_rand(111111, 999999);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => Crypt::encryptString($token),
            'token_expiry' => date('Y-m-d H:i:s', time() + (60 * 15))
        ]);

        $user->email_verified = 0;
        $daily_quote = DailyQuote::inRandomOrder()->first();
        if(!empty($daily_quote)){
            $user->daily_quote_id = $daily_quote->id;
        }
        if(!empty($daily_tip = DailyTip::inRandomOrder()->first())){
            $user->daily_tip_id = $daily_tip->id;
        }
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

        $not_settings = NotificationSetting::where('new_user_notification', 1);
        if($not_settings->count() > 0){
            foreach($not_settings->get() as $not_setting){
                AdminNotification::create([
                    'admin_id' => $not_setting->admin_id,
                    'title' => 'New User',
                    'body' => 'A New User by the name '.$user->name.' just signed up',
                    'page' => 'users',
                    'identifier' => $user->id,
                    'opened' => 0,
                    'status' => 1
                ]);
            }
        }

        if(!$login = $this->user_login($request->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        // $free_trial = SubscriptionPackage::where('free_trial', 1)->first();
        // if(!empty($free_trial)){
        //     $plan = PaymentPlan::where('subscription_package_id', $free_trial->id)->first();
        //     $sub = new SubscriptionController();
        //     $sub->subscribe($user->id, $free_trial->id, $plan->id, 0);
        // }

        self::check_ip($request, $user->id);

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
                'message' => 'Account already verified'
            ], 409);
        }

        $found = false;

        $tokens = EmailVerificationToken::where('user_id', $user->id)->orderBy('created_at', 'desc');
        if($tokens->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Verification Token'
            ], 409);
        }
        foreach($tokens->get() as $token){
            if((Crypt::decryptString($token->token) == $request->token) and ($token->token_expiry >= date('Y-m-d H:i:s'))){
                $found = true;
                break;
            }
        }
        if(!$found){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Token'
            ], 409);
        }

        $user = User::find($token->user_id);
        $user->email_verified = 1;
        $user->save();

        $tokens = EmailVerificationToken::where('user_id', $user->id);
        if($tokens->count() > 0){
            foreach($tokens->get() as $token){
                $token->delete();
            }
        }

        $user = self::user_details($user);

        self::log_activity($user->id, 'activate_email');

        return response([
            'status' => 'success',
            'message' => 'Email Verified Successfully',
            'data' => $user
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

        $token = mt_rand(111111, 999999);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => Crypt::encryptString($token),
            'token_expiry' => date('Y-m-d H:i:s', time() + (60 * 15))
        ]);

        $names = explode(' ', $user->name);
        $first_name = $names[0];
        Mail::to($user)->send(new EmailVerificationMail($first_name, $token));

        return response([
            'status' => 'success',
            'message' => 'Email Verification Token sent to '.$user->email
        ], 200);
    }

    private function user_login($email, $password){
        $user = User::where('email', $email)->where('deactivated', 0)->first();
        if(empty($user)){
            $this->errors = "Wrong Credentials";
            return false;
        }
        if($user->status != 1){
            $this->errors = 'Your account has been temporarily disabled. Kindly contact our customer support to get it back reinstated';
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
        if(empty($user->last_login_date)){
            $user->last_login_date = date('Y-m-d');
            $user->present_streak = 1;
            $user->longest_streak = 1;
            $user->total_logins = 1;
        } else {
            $last_date = $user->last_login_date;
            $user->last_login_date = date('Y-m-d');
            $yesterday = date('Y-m-d', time() - (60 * 60 * 24));
            if($last_date == $yesterday){
                $user->present_streak += 1;
                $user->total_logins += 1;
            } elseif($last_date < $yesterday){
                $user->present_streak = 1;
                $user->total_logins += 1;
            }
            if($user->present_streak > $user->longest_streak){
                $user->longest_streak = $user->present_streak;
            }
        }
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

    public function user_guide(){
        $user = User::find(self::user()->id);
        $user->user_guide = true;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'User Guide updated successfully'
        ], 200);
    }

    public static function user_details(User $user) : User
    {
        $user = User::find($user->id);
        $current_subscription = CurrentSubscription::where('user_id', $user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->orderBy('grace_end', 'desc')->first();
        if(!empty($current_subscription)){
            $user->current_subscription = $current_subscription;
           
            $user->subscription_package = SubscriptionPackage::where('id', $current_subscription->subscription_package_id)->first(['package', 'podcast_limit', 'article_limit', 'video_limit', 'book_limit', 'audio_limit', 'listen_and_learn_limit', 'read_and_reflect_limit', 'learn_and_do_limit', 'free_trial']);
             if($user->subscription_package->free_trial != 1){
                $user->question_type = "Dass21 Questions";
            } else {
                $user->question_type = "Basic Questions";
            }
        } else {
            $user->current_subscription = [];
            $user->question_type = "Basic Questions";
            $user->subscription_package = SubscriptionPackage::where('free_package', 1)->first(['package', 'podcast_limit', 'article_limit', 'audio_limit', 'video_limit', 'book_limit', 'listen_and_learn_limit', 'read_and_reflect_limit', 'learn_and_do_limit']);
        }

        if(!empty($user->daily_quote_id)){
            $user->daily_quote = DailyQuote::where('id', $user->daily_quote_id)->first(['quote', 'author']);
        } else {
            $user->daily_quote = null;
        }

        if(!empty($user->daily_tip_id)){
            $user->daily_tip = DailyTip::where('id', $user->daily_tip_id)->first(['tip']);
        } else {
            $user->daily_tip = null;
        }

        unset($user->daily_quote_id);
        unset($user->daily_tip_id);

        if(!empty($user->profile_photo)){
            $user->profile_photo = FileManagerController::fetch_file($user->profile_photo)->url;
        } else {
            $user->profile_photo = "";
        }

        $last_answer = QuestionAnswerSummary::where('user_id', $user->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        $user->dass_question = empty(QuestionAnswerSummary::where('user_id', $user->id)->where('question_type', 'dass_question')->first()) ? 'first' : 'subsequent';
        $user->next_question_date = !empty($last_answer) ? $last_answer->next_question : date('Y-m-d');
        $answered = DailyQuestionAnswer::where('user_id', $user->id)->where('answer_date', date('Y-m-d'));
        $user->daily_question = ($answered->count() < 1) ? true : false;
        $user->incomplete_answers = self::fetch_temp_answer($user->id);
        $user->first_time_dass = (QuestionAnswerSummary::where('user_id', $user->id)->where('question_type', 'dass_question')->count() < 1) ? true : false;
        $notification_setting = UserNotificationSetting::where('user_id', $user->id)->first();
        if(empty($notification_setting)){
            $notification_setting = UserNotificationSetting::create([
                'user_id' => $user->id,
                'system_notification' => 1,
                'goal_setting_notification' => 1,
                'resource_notification' => 1
            ]);
        }
        $user->notification_setting = $notification_setting;

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
        $daily_quote = DailyQuote::inRandomOrder()->first();
        if(!empty($daily_quote)){
            $user->daily_quote_id = $daily_quote->id;
            $user->save();
        }
        if(!empty($daily_tip = DailyTip::inRandomOrder()->first())){
            $user->daily_tip_id = $daily_tip->id;
            $user->save();
        }
        if($request->device_token != $user->device_token){
            $user->device_token = $request->device_token;
            $user->save();
        }
        if($request->web_token != $user->web_token){
            $user->web_token = $request->web_token;
            $user->save();
        }
        self::check_ip($request, $user->id);

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
        $token = mt_rand(111111, 999999);
        $user->token = Crypt::encryptString($token);
        $user->token_expiry = date('Y-m-d H:i:s', time() + (60 * 10));
        $user->save();

        $names = explode(' ', $user->name);
        $first_name = $names[0];
        Mail::to($user)->send(new ForgotPasswordMail($first_name, $token));

        self::log_activity($user->id, "forgot_password");

        return response([
            'status' => 'success',
            'message' => 'Password reset link sent to '.$request->email
        ], 200);
    }

    public function reset_password(ResetPasswordRequest $request){
        $user = User::where('email', $request->email)->first();
        if(Crypt::decryptString($user->token) != $request->token){
            return response([
                'status' => 'failed',
                'message' => 'Wrong OTP'
            ], 409);
        }
        if($user->token_expiry < date('Y-m-d H:i:s')){
            $user->token = null;
            $user->token_expiry = null;
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'OTP expired'
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

            $free_trial = SubscriptionPackage::where('free_trial', 1)->first();
            if(!empty($free_trial)){
                $plan = PaymentPlan::where('subscription_package_id', $free_trial->id)->first();
                $sub = new SubscriptionController();
                $sub->subscribe($user->id, $free_trial->id, $plan->id, 0);
            }

            $stripe = new StripeController();
            if($customer = $stripe->create_customer($user->name, $user->email)){
                StripeCustomer::create([
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'customer_data' => json_encode($customer)
                ]);
            }

            $not_settings = NotificationSetting::where('new_user_notification', 1);
            if($not_settings->count() > 0){
                foreach($not_settings->get() as $not_setting){
                    AdminNotification::create([
                        'admin_id' => $not_setting->admin_id,
                        'title' => 'New User',
                        'body' => 'A New User by the name '.$user->name.' just signed up',
                        'page' => 'users',
                        'identifier' => $user->id,
                        'opened' => 0,
                        'status' => 1
                    ]);
                }
            }
        }
        
        $token = auth('user-api')->login($user);
        $user->prev_login = !empty($user->last_login) ? $user->last_login : date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');
        if(empty($user->last_login_date)){
            $user->last_login_date = date('Y-m-d');
            $user->present_streak = 1;
            $user->longest_streak = 1;
            $user->total_logins = 1;
        } else {
            $last_date = $user->last_login_date;
            $user->last_login_date = date('Y-m-d');
            $yesterday = date('Y-m-d', time() - (60 * 60 * 24));
            if($last_date == $yesterday){
                $user->present_streak += 1;
                $user->total_logins += 1;
            } elseif($last_date < $yesterday){
                $user->present_streak = 1;
                $user->total_logins += 1;
            }
            if($user->present_streak > $user->longest_streak){
                $user->longest_streak = $user->present_streak;
            }
        }
        $user->save();

        $auth = [
            'token' => $token,
            'type' => 'Bearer',
            'expiry' => auth('user-api')->factory()->getTTL() * 60
        ];

        $daily_quote = DailyQuote::inRandomOrder()->first();
        if(!empty($daily_quote)){
            $user->daily_quote_id = $daily_quote->id;
            $user->save();
        }
        if(!empty($daily_tip = DailyTip::inRandomOrder()->first())){
            $user->daily_tip_id = $daily_tip->id;
            $user->save();
        }
        if($request->device_token != $user->device_token){
            $user->device_token = $request->device_token;
            $user->save();
        }
        if($request->web_token != $user->web_token){
            $user->web_token = $request->web_token;
            $user->save();
        }

        self::check_ip($request, $user->id);
        $user = self::user_details($user);
        $user->authorization = $auth;

        self::log_activity($user->id, "google_login");

        return response([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $user
        ], 200);
    }

    public function change_password(ChangePasswordRequest $request){
        $user = User::find(self::user()->id);

        if(!$this->user_login($user->email, $request->old_password)){
            return response([
                'status' => 'failed',
                'message' => 'Incorrect Password'
            ], 409);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Password successfully changed'
        ], 200);
    }

    public function change_name(ChangeNameRequest $request){
        $user = User::find(self::user()->id);
        $user->name = $request->name;

        $user->save();

        $user = self::user_details($user);

        return response([
            'status' => 'success',
            'message' => 'Name changed successfully',
            'data' => $user
        ], 200);
    }

    public function change_email(ChangeEmailRequest $request){
        $user = User::find(self::user()->id);

        if(!$login = $this->user_login($user->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Password'
            ], 409);
        }

        $user->email = $request->email;
        $user->email_verified = 0;
        $user->save();

        $user = self::user_details($user);
        $user->authorization = $login;

        return response([
            'status' => 'success',
            'message' => 'Email successfully changed',
            'data' => $user
        ], 200);
    }

    public function upload_profile_photo(UploadProfilePhotoRequest $request){
        $user = User::find(self::user()->id);

        $old_photo = "";
        if(!empty($user->profile_photo)){
            $old_photo = $user->profile_photo;
        }

        if(!$upload = FileManagerController::upload_file($request->profile_photo, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => "Profile Photo Upload failed"
            ], 500);
        }

        $user->profile_photo = $upload->id;
        $user->save();

        if(!empty($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Profile Photo Uploaded successfully',
            'data' => self::user_details($user)
        ], 200);
    }

    public function deactivate(DeactivateAccountRequest $request){
        $user = User::find(self::user()->id);

        $user->deactivated = 1;
        $user->save();

        $all = $request->all();
        $all['user_id'] = $user->id;
        $deactivation = UserDeactivation::create($all);

        auth('user-api')->logout();

        $not_settings = NotificationSetting::where('account_deactivation_notification', 1);
        if($not_settings->count() > 0){
            foreach($not_settings->get() as $setting){
                AdminNotification::create([
                    'admin_id' => $setting->admin_id,
                    'title' => 'Account Deactivation',
                    'body' => $user->name.' has just deactiated his/her account',
                    'page' => 'deactivated_users',
                    'identifier' => $deactivation->id,
                    'opened' => 0,
                    'status' => 1
                ]);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Account deactivated successfully'
        ], 200);
    }

    public function contact_us(ContactUsRequest $request){
        try{
            Mail::to('support@psychinsightsapp.com')->send(new ContactUsMail($request->message, $request->email, $request->name, $request->subject));
            return response([
                'status' => 'success',
                'message' => 'Message sent successfully. You\'ll receive a reply from us soonest'
            ], 200);
        } catch(Exception $e){
            return response([
                'status' => 'failed',
                'message' => 'There was an Error in sending the message'
            ], 500);
        }
    }
}
