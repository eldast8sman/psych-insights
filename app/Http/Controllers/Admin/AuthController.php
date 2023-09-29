<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Admin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateAccountRequest;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Mail\Admin\AddAdminMail;
use App\Mail\Admin\ForgotPasswordMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private $errors;
    
    public function store(StoreAdminRequest $request){
        $user = self::user();
        if($user->role != "super"){
            return response([
                'status' => 'failed',
                'message' => 'Not Authorized to add an Admin'
            ], 409);
        }
        if($admin = Admin::create($request->all())){
            $admin->token = base64_encode($admin->id."PsychInsights".Str::random(20));
            $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
            $admin->save();

            Mail::to($admin)->send(new AddAdminMail($admin->name, $admin->token));
            return response([
                'status' => 'success',
                'message' => 'Admin added successfully',
                'data' => $admin
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'Admin creation failed'
            ], 409);
        }
    }

    public static function user(){
        return auth('admin-api')->user();
    }

    public function storeAdmin(Request $request){
        $admin = Admin::create([
            'email' => $request->email,
            'name' => $request->name,
            'role' => $request->role
        ]);

        $admin->token = base64_encode($admin->id."PsychInsights".Str::random(20));
        $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
        $admin->save();

        Mail::to($admin)->send(new AddAdminMail($admin->name, $admin->token));
        return response([
            'status' => 'success',
            'message' => 'Admin added successfully',
            'data' => $admin
        ], 200);
    }

    public function byToken($token){
        $admin = Admin::where('token', $token)->first();
        if(empty($admin)){
            return response([
                'status' => 'failed',
                'message' => 'No Admin Account was fetched'
            ], 404);
        }

        if($admin->token_expiry < date('Y-m-d H:i:s')){
            $admin->token = base64_encode($admin->id."PsychInsights".Str::random(20));
            $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
            $admin->save();

            Mail::to($admin)->send(new AddAdminMail($admin->name, $admin->token));
            return response([
                'status' => 'failed',
                'message' => 'Link has expired. However another link has been sent to '.$admin->email
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Admin Account fetched successfully',
            'data' => $admin
        ], 200);
    }

    public function activate_account(ActivateAccountRequest $request){
        $admin = Admin::where('token', $request->token)->first();
        if(empty($admin)){
            return response([
                'status' => 'failed',
                'message' => 'No Admin Account was fetched'
            ], 404);
        }

        if($admin->token_expiry < date('Y-m-d H:i:s')){
            $admin->token = base64_encode($admin->id."PsychInsights".Str::random(20));
            $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
            $admin->save();

            Mail::to($admin)->send(new AddAdminMail($admin->name, $admin->token));
            return response([
                'status' => 'failed',
                'message' => 'Link has expired. However another link has been sent to '.$admin->email
            ], 404);
        }

        $admin->password = Hash::make($request->password);
        $admin->status = 1;
        $admin->token = null;
        $admin->token_expiry = null;
        $admin->save();

        $auth = $this->login_function($admin->email, $request->password);
        if(!$auth){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Account activated successfully',
            'data' => $auth
        ], 200);
    }

    public function login_function($email, $password){
        $admin = Admin::where('email', $email)->first();
        if(empty($admin)){
            $this->errors = "Wrong Credentials";
            return false;
        }
        if($admin->status != 1){
            $this->errors = "Account is not yet activated";
            return false;
        }
        $token = auth('admin-api')->attempt([
            'email' => $email,
            'password' => $password
        ]);
        if(!$token){
            $this->errors = "Wrong Credentials";
            return false;
        }
        $admin->prev_login = !empty($admin->last_login) ? $admin->last_login : date('Y-m-d H:i:s');
        $admin->last_login = date('Y-m-d H:i:s');
        $admin->save();

        $auth = [
            'token' => $token,
            'type' => 'Bearer',
            'expiry' => auth('admin-api')->factory()->getTTL() * 60
        ];

        $admin->authorization = $auth;
        return $admin;
    }

    public function login(LoginRequest $request){
        $auth = $this->login_function($request->email, $request->password);
        if(!$auth){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }
        return response([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $auth
        ], 200);
    }

    public function forgot_password(ForgotPasswordRequest $request){
        $admin = Admin::where('email', $request->email)->first();
        if($admin->status != 1){
            return response([
                'status' => 'failed',
                'message' => 'Account is not yet activated'
            ], 404);
        }
        $admin->token = md5('PsychInsights'.$admin->id.time().Str::random(20));
        $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 10));
        $admin->save();

        Mail::to($admin)->send(new ForgotPasswordMail($admin->name, $admin->token));
        return response([
            'status' => 'success',
            'message' => 'Password Reset Link sent to '.$admin->email
        ], 200);
    }

    public function reset_password(ActivateAccountRequest $request){
        $admin = Admin::where('token', $request->token)->first();
        if($admin->status != 1){
            return response([
                'status' => 'failed',
                'message' => 'Account not yet activated'
            ], 409);
        }

        if($admin->token_expiry < date('Y-m-d H:i:s')){
            $admin->token = null;
            $admin->token_expiry = null;
            $admin->save();
            return response([
                'status' => 'failed',
                'message' => 'Link has expired'
            ], 409);
        }

        $admin->password = Hash::make($request->password);
        $admin->token = null;
        $admin->token_expiry = null;
        $admin->save();

        return response([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ], 200);
    }

    public function change_password(ChangePasswordRequest $request){
        $user = self::user();
        if(!$this->login_function($user->email, $request->old_password)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Password'
            ], 409);
        }
        $admin = Admin::find($user->id);
        $admin->password = Hash::make($request->password);
        $admin->save();
        $admin = $this->login_function($admin->email, $request->password);

        return response([
            'status' => 'success',
            'message' => 'Password changed successfully',
            'data' => $admin
        ], 200);
    }

    public function me(){
        $user = self::user();
        return response([
            'status' => 'success',
            'message' => 'User details fetched successfully',
            'data' => $user
        ], 200);
    }

    public function dashboard(){
        
    }

    public function logout(){
        auth('admin-api')->logout();

        return response([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], 200);
    }
}
