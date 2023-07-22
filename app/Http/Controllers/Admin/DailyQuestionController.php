<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDailyQuestionOptionRequest;
use App\Http\Requests\Admin\StoreDailyQuestionRequest;
use App\Http\Requests\Admin\UpdateDailyQuestionRequest;
use App\Models\Category;
use App\Models\DailyQuestion;
use App\Models\DailyQuestionOption;
use Illuminate\Http\Request;

class DailyQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function categories($question_id){
        $categories = [];

        $question = DailyQuestion::find($question_id);

        $category_ids = explode(',', $question->categories);
        foreach($category_ids as $id){
            $category = Category::find(trim($id));
            if(!empty($category)){
                $categories[] = $category->category;
            }
        }

       return $categories;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = DailyQuestion::orderBy('created_at', 'asc');
        if($questions->count() > 0){
            $questions = $questions->get();
            foreach($questions as $question){
                $question->options = $question->options()->get();

                $question->categories = $this->categories($question->id);
            }

            return response([
                'status' => 'success',
                'message' => 'Daily Questions fetched',
                'data' => $questions
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'No Question was fetched',
                'data' => null
            ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDailyQuestionRequest $request)
    {
        $question = DailyQuestion::create([
            'question' => $request->question,
            'categories' => join(',', $request->categories)
        ]);
        if(!$question){
            return response([
                'status' => 'failed',
                'message' => 'Question Upload Failed'
            ], 500);
        }

        $question->categories = $this->categories($question->id);

        foreach($request->options as $option){
            $options[] = DailyQuestionOption::create([
                'daily_question_id' => $question->id,
                'option' => $option['option'],
                'value' => $option['value']
            ]);
        }

        $question->options = $question->options()->get();

        return response([
            'status' => 'success',
            'message' => 'Daily Question added successfully',
            'data' => $question
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyQuestion $question)
    {
        $question->categories = $this->categories($question->id);
        $question->options = $question->options()->get();

        return response([
            'status' => 'success',
            'message' => 'Daily Question fetched successfully',
            'data' => $question
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDailyQuestionRequest $request, DailyQuestion $question)
    {
        $question->question = $request->question;
        $question->categories = join(',', $request->categories);
        $question->save();

        $question->categories = $this->categories($question->id);

        foreach($request->options as $optioning){
            $option = DailyQuestionOption::find($optioning['id']);
            $option->option = $optioning['option'];
            $option->value = $optioning['value'];
            $option->save();
            $options[] = $option;
        }

        $question->options = $question->options()->get();

        return response([
            'status' => 'success',
            'message' => 'Daily Question updated successfully',
            'data' => $question
        ], 200);
    }

    public function add_option(StoreDailyQuestionOptionRequest $request, $question_id){
        $question = DailyQuestion::find($question_id);
        if(empty($question)){
            return response([
                'status' => 'failed',
                'message' => 'No Daily Question was fethced'
            ], 404);
        }
        $option = DailyQuestionOption::create([
            'daily_question_id' => $question->id,
            'option' => $request->option,
            'value' => $request->value
        ]);
        if(!$option){
            return response([
                'status' => 'failed',
                'message' => 'Option creation failed'
            ], 500);
        }

        $question->categories = $this->categories($question->id);

        $question->options = $question->options()->get();
        return response([
            'status' => 'failed',
            'message' => 'Option added to question successfully',
            'data' => $question
        ], 200);
    }

    public function remove_option(DailyQuestionOption $option){
        $option->delete();

        $question = DailyQuestion::find($option->daily_question_id);
        $question->categories = $this->categories($question->id);
        $question->options = $question->options()->get();

        return response([
            'status' => 'success',
            'message' => 'Option successfully deleted',
            'data' => $question
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyQuestion $question)
    {
        $question->delete();
        $options = DailyQuestionOption::where('daily_question_id', $question->id);
        if($options->count() > 0){
            foreach($options->get() as $option){
                $option->delete();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Daily Question successfuly deleted'
        ], 200);
    }
}
