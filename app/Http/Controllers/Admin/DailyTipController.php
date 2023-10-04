<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDailyTipRequest;
use App\Models\DailyTip;
use Illuminate\Http\Request;

class DailyTipController extends Controller
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
        $tips = DailyTip::all();

        return response([
            'status' => 'success',
            'message' => 'Daily Tips fetched successfully',
            'data' => $tips
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDailyTipRequest $request)
    {
        $tip = DailyTip::create($request->all());

        return response([
            'status' => 'success',
            'message' => 'Daily Tip created successfully',
            'data' => $tip
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyTip $tip)
    {
        return response([
            'status' => 'success',
            'message' => 'Daily Tip fetched successfully',
            'data' => $tip
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreDailyTipRequest $request, DailyTip $tip)
    {
        $tip->update($request->all());

        return response([
            'status' => 'success',
            'message' => 'Daily Tip updated successfully',
            'data' => $tip
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyTip $tip)
    {
        $tip->delete();

        return response([
            'status' => 'success',
            'message' => 'Tip deleted successfully',
            'data' => $tip
        ], 200);
    }
}
