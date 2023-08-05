<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoCodeRequest;
use App\Http\Requests\Admin\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
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
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $status = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $codes = PromoCode::orderBy('promo_code', 'asc');
        if(!empty($search)){
            $codes = $codes->where('promo_code', 'like', '%'.$search.'%');
        }
        if($status !== NULL){
            $codes = $codes->where('status', $status);
        }

        if($codes->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Promo Code was fetched',
                'data' => null
            ], 200);
        }

        $codes = $codes->paginate($limit);
        foreach($codes as $code){
            $code->scope = explode(',', $code->scope);
        }

        return response([
            'status' => 'success',
            'message' => 'Promo Codes fetched successfully',
            'data' => $codes
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoCodeRequest $request)
    {
        $code = PromoCode::create([
            'promo_code' => $request->promo_code,
            'scope' => (isset($request->scope) && !empty($request->scope)) ? join(',', $request->scope) : '',
            'percentage_off' => $request->percentage_off,
            'usage_limit' => $request->usage_limit,
            'status' => 1
        ]);

        $code->scope = explode(',', $code->scope);

        return response([
            'status' => 'success',
            'message' => 'Promo Code added successfully',
            'data' => $code
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(PromoCode $code)
    {
        $code->scope = explode(',', $code->scope);

        return response([
            'status' => 'success',
            'message' => 'Promo Code fetched successfully',
            'data' => $code
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoCodeRequest $request, PromoCode $code)
    {
        $code->promo_code = $request->promo_code;
        $code->scope = (isset($request->scope) && !empty($request->scope)) ? join(',', $request->scope) : '';
        $code->percentage_off = $request->percentage_off;
        $code->usage_limit = $request->usage_limit;
        $code->save();

        $code->scope = explode(',', $code->scope);

        return response([
            'status' => 'success',
            'message' => 'Promo Code updated successfully',
            'data' => $code
        ], 200);
    }

    public function activation(PromoCode $code){
        $code->status = ($code->status == 1) ? 0 : 1;
        $code->save();

        return response([
            'status' => 'success',
            'message' => 'Promo Code activation successful',
            'data' => $code
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromoCode $code)
    {
        $code->delete();

        return response([
            'status' => 'success',
            'message' => 'Promo Code deleted successfully'
        ], 200);
    }
}
