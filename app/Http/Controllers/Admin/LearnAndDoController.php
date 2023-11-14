<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\LearnAndDo;
use Illuminate\Http\Request;
use App\Models\LearnAndDoActivity;
use App\Models\LearnAndDoQuestion;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreLearnAndDoRequest;
use App\Http\Requests\Admin\StoreLearnAndDoActivityRequest;
use App\Http\Requests\Admin\StoreLearnAndDoQuestionRequest;
use App\Http\Requests\Admin\UpdateLearnAndDoActivityRequest;
use App\Http\Requests\Admin\UpdateLearnAndDoRequest;

class LearnAndDoController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function learn_and_do(LearnAndDo $learn) : LearnAndDo
    {
        if(!empty($learn->photo)){
            $learn->photo = FileManagerController::fetch_file($learn->photo);
        }

        if(!empty($learn->categories)){
            $categories = [];
            $categs = explode(',', $learn->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $learn->categories = $categories;
        }

        $l_activities = [];
        $activities = LearnAndDoActivity::where('learn_and_do_id', $learn->id);
        if($activities->count() > 0){
            foreach($activities->get() as $activity){
                $l_activities[] = $this->activity($activity);
            }
        }
        $learn->activities = $l_activities;

        $sub_level = $learn->subscription_level;
        if($sub_level > 0){
            $package = SubscriptionPackage::where('level', $sub_level)->first();
            $subscription_level = $package->package;
        } else {
            $subscription_level = "Basic";
        }

        $learn->subscription_level = $subscription_level;

        return $learn;
    }

    public function activity(LearnAndDoActivity $activity) : LearnAndDoActivity
    {
        $questions = LearnAndDoQuestion::where('learn_and_do_id', $activity->learn_and_do_id)->where('activity_id', $activity->id)->get();

        $activity->questions = $questions;

        return $activity;
    }

    public function index(){
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['filter']) ? (int)$_GET['filter'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $learns = LearnAndDo::where('status', '>=', 0);
        if(!empty($search)){
            $learns = $learns->where('title', 'like', '%'.$search.'%');
        }
        if($filter != NULL){
            $learns = $learns->where('status', $filter);
        }
        if($filter !== 0){
            $learns = $learns->where('status', '!=', 0);
        }
        $learns->orderBy('title', $sort);

        if($learns->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Strategy was fetched',
                'data' => null
            ], 200);
        }

        $a_learns = [];
        $learns = $learns->paginate($limit);
        foreach($learns as $learn){
            $learn = $this->learn_and_do($learn);
        }

        return response([
            'status' => 'success',
            'message' => 'Learn and Do Strategies fetched successfully',
            'data' => $learns
        ], 200);
    }

    public function store(StoreLearnAndDoRequest $request){
        $all = $request->except(['photo', 'categories']);
        if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
            return response([
                'status' => 'failed',
                'message' => 'Startegy Photo could not be uploaded'
            ], 409);
        }
        $all['photo'] = $upload->id;
        $all['categories'] = join(',', $request->categories);

        if(!$learn = LearnAndDo::create($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Learn And Do Upload Failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Learn and Do Upload successful',
            'data' => $this->learn_and_do($learn->id)
        ], 200);
    }

    public function store_activity(StoreLearnAndDoActivityRequest $request, LearnAndDo $learn){
        $all = $request->except(['questions']);
        $all['learn_and_do_id'] = $learn->id;

        if(!$activity = LearnAndDoActivity::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Activity Upload Failed'
            ], 409);
        }

        if(!empty($request->questions)){
            $questions = $request->questions;
            foreach($questions as $question){
                LearnAndDoQuestion::create([
                    'learn_and_do_id' => $learn->id,
                    'activity_id' => $activity->id,
                    'question' => $question['question'],
                    'answer_type' => $question['answer_type'],
                    'number_of_list' => !empty($question['number_of_list']) ? $question['number_of_list'] : null,
                    'minimum' => isset($question['minimum']) ? $question['minimum'] : null,
                    'maximum' => isset($question['maximum']) ? $question['maximum'] : null
                ]);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Activity added to Strategy',
            'data' => $this->learn_and_do($learn->id)
        ], 200);
    }

    public function show(LearnAndDo $learn){
        $learn = $this->learn_and_do($learn->id);

        return response([
            'status' => 'success',
            'message' => 'Learn and Do Strategy fetched successfully',
            'data' => $learn
        ], 200);
    }

    public function show_activity(LearnAndDoActivity $activity){
        $activity = $this->activity($activity);

        return response([
            'status' => 'success',
            'message' => 'Activity fetched successfully',
            'data' => $activity
        ], 200);
    }

    public function show_question(LearnAndDoQuestion $question){
        return response([
            'status' => 'failed',
            'message' => 'Question fetched successfully',
            'data' => $question
        ], 200);
    }

    public function store_question(StoreLearnAndDoQuestionRequest $request, LearnAndDoActivity $activity){
        $all = $request->all();
        $all['learn_and_do_id'] = $activity->learn_and_do_id;
        $all['activity_id'] = $activity->id;

        if(!LearnAndDoQuestion::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Question was not added'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Question added to Activity successfully',
            'data' => $this->activity($activity)
        ], 200);
    }

    public function update(UpdateLearnAndDoRequest $request, LearnAndDo $learn){
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Photo could not be uploaded'
                ], 500);
            }

            $all['photo'] = $upload->id;
            $old_photo = $learn->photo;
        }

        $categories = [];
        foreach($request->categories as $cat_id){
            $category = Category::where('id', trim($cat_id))->orWhere('category', trim($cat_id))->first();
            if(!empty($category)){
                if(!in_array($category->id, $categories)){
                    $categories[] = $category->id;
                }
            }
        }
        $all['categories'] = join(',', $categories);

        if(!$learn->update($all)){
            if(isset($all['photo'])){
                FileManagerController::delete($all['photo']);
            }

            return response([
                'status' => 'failed',
                'message' => 'Learn and Do Strategy Update failed'
            ], 500);
        }

        if(isset($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Learn and Do Strategy updated successfully',
            'data' => $this->learn_and_do($learn->id)
        ], 200);
    }

    public function update_activity(UpdateLearnAndDoActivityRequest $request, LearnAndDoActivity $activity){
        $all = $request->all();
        if(!$activity->update($all)){
            return response([
                'status' => 'failed',
                'message' => 'Activity Update failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Activity updated successfully',
            'data' => $this->activity($activity)
        ], 200);
    }

    public function update_question(StoreLearnAndDoQuestionRequest $request, LearnAndDoQuestion $question){
        if(!$question->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Question Update Failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Question Update successful',
            'data' => $question
        ], 200);
    }

    public function destroy_activity(LearnAndDoActivity $activity){
        $activity->delete();
        $questions = LearnAndDoQuestion::where('activity_id', $activity->id);
        if($questions->count()){
            foreach($questions->get() as $question){
                $question->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Activity deleted successfully'
        ], 200);
    }

    public function destroy_question(LearnAndDoQuestion $question){
        $question->delete();

        return response([
            'status' => 'success',
            'message' => 'Activity Question deleted successfully'
        ], 200);
    }

    public function destroy(LearnAndDo $learn){
        $learn->status = 0;
        $learn->save();

        return response([
            'status' => 'success',
            'message' => 'Learn and Do strategy deleted successfully'
        ], 200);
    }
}
