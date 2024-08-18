<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    private $user;
    private $time;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
        if(empty($this->user->last_timezone)){
            $this->time = Carbon::now();
        } else {
            $this->time = Carbon::now($this->user->last_timezone);
        }
    }

    
    public function index()
    {
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $journals = Journal::where('user_id', $this->user->id)->where('pinned', 0);
        if($journals->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched',
                'data' => []
            ], 200);
        }

        $journals = $journals->orderBy('created_time', 'desc')->orderBy('created_at', 'desc')->paginate($limit);
        foreach($journals as $journal){
            unset($journal->user_id);
        }

        return response([
            'status' => 'success',
            'message' => 'Journals fetched successfully',
            'data' => $journals
        ], 200);
    }

    public function pinned_journals(){
        $journals = Journal::where('user_id', $this->user->id)->where('pinned', 1)->orderBy('updated_at', 'desc');
        if($journals->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Pinned Journal was fetched',
                'data' => null
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Pinned Journals fetched successfully',
            'data' => $journals->get()
        ], 200);
    }

    public function store(StoreJournalRequest $request)
    {
        $current_time = $this->time->format('Y-m-d H:i:s');
        $journal = Journal::create([
            'user_id' => $this->user->id,
            'journal' => $request->journal,
            'title' => $request->title,
            'color' => $request->color,
            'created_time' => $current_time,
            'updated_time' => $current_time
        ]);

        unset($journal->user_id);

        self::log_activity($this->user->id, "store_journal", 'journals', $journal->id);

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

        $all = $request->all();
        $all['updated_time'] = $this->time->format('Y-m-d H:i:s');

        $journal->update($request->all());

        unset($journal->user_id);

        self::log_activity($this->user->id, "update_journal", 'journals', $journal->id);

        return response([
            'status' => 'success',
            'message' => 'Journal updated successfully',
            'data' => $journal
        ], 200);
    }

    public function pin_journal(Journal $journal){
        if($journal->user_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Journal was fetched'
            ], 404);
        }

        if($journal->pinned == 0){
            if(Journal::where('user_id', $this->user->id)->where('pinned', 1)->count() >= 3){
                return response([
                    'status' => 'failed',
                    'message' => 'You cannot Pin more than three(3) Journals'
                ], 409);
            }
            $journal->pinned = 1;
            $journal->save();

            return response([
                'status' => 'success',
                'message' => 'Journal pinned successfully'
            ], 200);
        } else {
            $journal->pinned = 0;
            $journal->save();

            return response([
                'status' => 'success',
                'message' => 'Journal unpinned successfully'
            ], 200);
        }
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

        self::log_activity($this->user->id, "delete_journal", 'journals', $journal->id);

        return response([
            'status' => 'success',
            'message' => 'Journal deleted',
            'data' => $journal
        ], 200);
    }
}
