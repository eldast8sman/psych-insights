<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\Journal;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    
    public function index()
    {
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $journals = Journal::where('user_id', $this->user->id);
        if($journals->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched',
                'data' => []
            ], 200);
        }

        $journals = $journals->paginate($limit);
        foreach($journals as $journal){
            unset($journal->user_id);
        }

        return response([
            'status' => 'success',
            'message' => 'Journals fetched successfully',
            'data' => $journals
        ], 200);
    }

    public function store(StoreJournalRequest $request)
    {
        $journal = Journal::create([
            'user_id' => $this->user->id,
            'journal' => $request->journal
        ]);

        unset($journal->user_id);

        self::log_activity($this->user->id, "store-journal", 'journals', $journal->id);

        return response([
            'status' => 'success',
            'message' => 'Journal added successfully',
            'data' => $journal
        ], 200);
    }

    public function show(Journal $journal)
    {
        if($journal->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched'
            ], 404);
        }

        unset($journal->user_id);

        return response([
            'status' => 'success',
            'message' => 'Journal fetched successfully',
            'data' => $journal
        ], 200);
    }

    public function update(StoreJournalRequest $request, Journal $journal)
    {
        if($journal->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched'
            ], 404);
        }

        $journal->journal = $request->journal;
        $journal->save();

        unset($journal->user_id);

        self::log_activity($this->user->id, "update-journal", 'journals', $journal->id);

        return response([
            'status' => 'success',
            'message' => 'Journal updated successfully',
            'data' => $journal
        ], 200);
    }

    public function destroy(Journal $journal)
    {
        if($journal->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched'
            ], 404);
        }

        $journal->delete();

        unset($journal->user_id);

        self::log_activity($this->user->id, "delete-journal", 'journals', $journal->id);

        return response([
            'status' => 'success',
            'message' => 'Journal deleted',
            'data' => $journal
        ], 200);
    }
}
