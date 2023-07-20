<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Admin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\Admin\AddAdminMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;

class AdminController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function index(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $admins = Admin::orderBy('name', $sort);
        if(!empty($search)){
            $admins = $admins->where('name', 'like', '%'.$search.'%');
        }
        if($admins->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Admin was fetched'
            ], 404);
            exit;
        }
        return response([
            'status' => 'success',
            'message' => 'Admins fetched successfully',
            'data' => $admins
        ], 200);
    }

    public function store(StoreAdminRequest $request){
        if($this->user->role != "super"){
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

    public function resend_activation_link(Admin $admin){
        if($this->user->role != "super"){
            return response([
                'status' => 'failed',
                'message' => 'You are not authorised to add an Admin'
            ], 409);
            exit;
        }
        if($admin->status == 1){
            return response([
                'status' => 'failed',
                'message' => 'This Admin is already active'
            ], 409);
            exit;
        }
        $admin->token = base64_encode($admin->id."PsychInsights".Str::random(20));
        $admin->token_expiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
        $admin->save();

        Mail::to($admin)->send(new AddAdminMail($admin->name, $admin->token));
        return response([
            'status' => 'success',
            'message' => 'Admin Activation Mail resent',
            'data' => $admin
        ], 200);
    }

    public function show(Admin $admin){
        if(empty($admin)){
            return response([
                'status' => 'failed',
                'message' => 'No Admin was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Admin fetched successfully',
            'data' => $admin
        ], 200);
    }

    public function update(UpdateAdminRequest $request, Admin $admin){
        if($this->user->role != 'super'){
            return response([
                'status' => 'failed',
                'message' => 'Not authorized to carry out this action'
            ], 409);
        }
        if(Admin::where('email', $request->email)->where('id', '<>', $admin->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'There is another Admin with this Email'
            ], 422);
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->save();

        return response([
            'status' => 'success',
            'message' => 'Admin updated successfully',
            'data' => $admin
        ], 200);
    }
    
    public function account_activation(Admin $admin){
        if($this->user->role != "super"){
            return response([
                'status' => 'failed',
                'message' => 'Not authorized'
            ], 409);
        }
        if($admin->status == 0){
            $admin->status = 1;
            $message = 'Admin activated successfully';
        } else {
            $admin->status = 0;
            $message = 'Admin successfully deactivated';
        }
        $admin->save();

        return response([
            'status' => 'success',
            'message' => $message,
            'data' => $admin
        ], 200);
    }

    public function destroy(Admin $admin){
        if($this->user->role != "super"){
            return response([
                'status' => 'failed',
                'message' => 'Not authorized'
            ], 409);
        }

        $admin->delete();
        return response([
            'status' => 'success',
            'message' => 'Admin successfully deleted',
            'data' => $admin
        ], 200);
    }
}
