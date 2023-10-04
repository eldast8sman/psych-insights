<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDailyQuoteRequest;
use App\Models\DailyQuote;
use Illuminate\Http\Request;

class DailyQuoteController extends Controller
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
        $quotes = DailyQuote::all();
        return response([
            'status' => 'success',
            'message' => 'Daily Quotes saved successfully',
            'data' => $quotes
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDailyQuoteRequest $request)
    {
        $quote = DailyQuote::create($request->all());

        return response([
            'status' => 'success',
            'message' => 'Daily Quote added successfully',
            'data' => $quote
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyQuote $quote)
    {
        return response([
            'status' => 'success',
            'message' => 'Daily QUote fetched succesfully',
            'data' => $quote
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreDailyQuoteRequest $request, DailyQuote $quote)
    {
        $quote->update($request->all());

        return response([
            'status' => 'success',
            'message' => 'Daily Quote updated successfully',
            'data' => $quote
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyQuote $quote)
    {
        $quote->delete();

        return response([
            'status' => 'success',
            'message' => 'Daily Quote deleted successfully'
        ], 200);
    }
}
