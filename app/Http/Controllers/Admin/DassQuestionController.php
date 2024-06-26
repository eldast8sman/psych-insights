<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\DassQuestion;
use Illuminate\Http\Request;
use App\Models\DassQuestionOption;
use App\Models\DistressScoreRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScoreRangeRequest;
use App\Http\Requests\Admin\StoreDassQuestionRequest;
use App\Http\Requests\Admin\UpdateDassQuestionRequest;
use App\Http\Requests\Admin\StoreDassQuestionOptionsRequest;
use App\Http\Requests\Admin\UpdateDassQuestionOptionsRequest;

class DassQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function options(){
        return DassQuestionOption::orderBy('value', 'asc')->get();
    }

    public function categories($question_id){
        $categories = [];

        $question = DassQuestion::find($question_id);

        $category_ids = explode(',', $question->categories);
        foreach($category_ids as $id){
            $category = Category::find(trim($id));
            if(!empty($category)){
                $categories[] = $category->category;
            }
        }

       return $categories;
    }

    public function add_options(StoreDassQuestionOptionsRequest $request){
        foreach($request->options as $option){
            DassQuestionOption::create([
                'option' => $option['option'],
                'value' => $option['value']
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Options entered successfully',
            'data' => $this->options()
        ], 200);
    }

    public function fetch_options(){
        return response([
            'status' => 'success',
            'message' => 'Options fetched successfully',
            'data' => $this->options()
        ], 200);
    }

    public function update_options(UpdateDassQuestionOptionsRequest $request){
        foreach($request->options as $options){
            $option = DassQuestionOption::find($options['id']);
            $option->option = $options['option'];
            $option->value = $options['value'];
            $option->save();
        }

        return response([
            'status' => 'success',
            'message' => 'Options updated successfully',
            'data' => $this->options()
        ], 200);
    }

    public function remove_option(DassQuestionOption $option){
        $option->delete();

        return response([
            'status' => 'success',
            'message' => 'Option deleted successfully',
            'data' => $this->options()
        ], 200);
    }

    public function index()
    {
        $questions = DassQuestion::orderBy('created_at', 'asc');
        if($questions->count() < 1){
            return response ([
                'status' => 'failed',
                'message' => 'No Question was fetched',
                'data' => null
            ], 200);
        }

        $questions = $questions->get();
        foreach($questions as $question){
            $question->categories = $this->categories($question->id);

            $question->options = $this->options();
        }

        return response([
            'status' => 'success',
            'message' => 'Questions fetched successfully',
            'data' => $questions
        ], 200);
    }

    public function store(StoreDassQuestionRequest $request)
    {
        $question = DassQuestion::create([
            'question' => $request->question,
            'categories' => join(',', $request->categories)
        ]);
        if(!$question){
            return response([
                'status' => 'failed',
                'message' => 'Question upload failed'
            ], 500);
        }

        $question->categories = $this->categories($question->id);
        $question->options = $this->options();

        return response([
            'status' => 'success',
            'message' => 'Question added successfully',
            'data' => $question
        ], 200);
    }

    public function show(DassQuestion $question)
    {
        $question->categories = $this->categories($question->id);
        $question->options = $this->options();

        return response([
            'status' => 'success',
            'message' => 'Question successfully fetched',
            'data' => $question
        ], 200);
    }

    public function update(UpdateDassQuestionRequest $request, DassQuestion $question){
        $question->question = $request->question;
        $caategory_ids = [];
        foreach($request->categories as $category){
            $categ = Category::where('id', $category)->orWhere('category', $category)->first();
            if(!empty($categ)){
                $caategory_ids[] = $categ->id;   
            }
        }
        $question->categories = join(',', $caategory_ids);
        $question->save();

        $question->categories = $this->categories($question->id);
        $question->options = $this->options();

        return response([
            'status' => 'success',
            'message' => 'Question updated successfully',
            'data' => $question
        ], 200);
    }

    public function destroy(DassQuestion $question)
    {
        $question->delete();

        return response([
            'status' => 'success',
            'message' => 'Question successfully deleted',
            'data' => $question
        ], 200);
    }
}
