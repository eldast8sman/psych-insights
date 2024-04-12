<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInterestRequest;
use App\Models\Interest;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function index()
    {
        $interests = Interest::orderBy('interest', 'asc')->get(['interest']);

        return response([
            'status' => 'success',
            'message' => 'Interests fetched successfully',
            'data' => $interests
        ], 200);
    }

    public function store(StoreInterestRequest $request)
    {
        $interest = Interest::create($request->all());

        return response([
            'status' => 'success',
            'message' => 'Interest Added successfully',
            'data' => $interest
        ], 200);
    }

    public function show(Interest $interest)
    {
        unset($interest->total_users);

        return response([
            'status' => 'success',
            'message' => 'Interest fetched successfully',
            'data' => $interest
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreInterestRequest $request, Interest $interest)
    {
        $interest->interest = $request->interest;
        $interest->save();
        unset($interest->total_users);

        return response([
            'status' => 'success',
            'message' => 'Interest updated successfully',
            'data' => $interest
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Interest $interest)
    {
        $interest->delete();

        return response([
            'status' => 'success',
            'message' => 'Interest deleted successfully',
            'data' => $interest
        ], 200);
    }
}
