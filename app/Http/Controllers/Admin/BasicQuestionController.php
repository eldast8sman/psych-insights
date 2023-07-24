<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\BasicQuestion;
use App\Models\BasicQuestionOption;
use App\Http\Controllers\Controller;
use App\Models\BasicQuestionSpecialOption;
use App\Http\Requests\Admin\StoreBasicQuestionRequest;
use App\Http\Requests\Admin\StoreBasicQuestionOptionsRequest;
use App\Http\Requests\Admin\UpdateBasicQuestionOptionsRequest;
use App\Http\Requests\Admin\UpdateBasicQuestionRequest;

class BasicQuestionController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public function options(){
        return BasicQuestionOption::orderBy('value', 'desc')->get();
    }

    public function fetch_question_options($question_id){
        $question = BasicQuestion::find($question_id);
        if($question->special_options == 1){
            $options = BasicQuestionSpecialOption::where('basic_question_id', $question->id)->orderBy('value', 'desc')->get();
        } else {
            $options = $this->options();
        }

        return $options;
    }

    public function add_options(StoreBasicQuestionOptionsRequest $request){
        foreach($request->options as $option){
            BasicQuestionOption::create([
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

    public function update_options(UpdateBasicQuestionOptionsRequest $request){
        foreach($request->options as $options){
            $option = BasicQuestionOption::find($options['id']);
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

    public function remove_option(BasicQuestionOption $option){
        $option->delete();

        return response([
            'status' => 'success',
            'message' => 'Option deleted successfully',
            'data' => $this->options()
        ], 200);
    }

    public function categories($question_id){
        $categories = [];

        $question = BasicQuestion::find($question_id);

        $category_ids = explode(',', $question->categories);
        foreach($category_ids as $id){
            $category = Category::find(trim($id));
            if(!empty($category)){
                $categories[] = $category->category;
            }
        }

       return $categories;
    }

    public function index()
    {
        $questions = BasicQuestion::orderBy('created_at', 'desc');
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

            $question->options = $this->fetch_question_options($question->id);
        }

        return response([
            'status' => 'success',
            'message' => 'Questions fetched successfully',
            'data' => $questions
        ], 200);
    }

    public function store(StoreBasicQuestionRequest $request)
    {
        $question = BasicQuestion::create([
            'question' => $request->question,
            'categories' => join(',', $request->categories),
            'is_k10' => $request->is_k10,
            'special_options' => $request->special_options
        ]);
        if(!$question){
            return response([
                'status' => 'failed',
                'message' => 'Question upload failed'
            ], 500);
        }

        if($question->special_options == 1){
            foreach($request->options as $option){
                BasicQuestionSpecialOption::create([
                    'basic_question_id' => $question->id,
                    'option' => $option['option'],
                    'value' => $option['value']
                ]);
            }
        }

        $question->categories = $this->categories($question->id);
        $question->options = $this->fetch_question_options($question->id);

        return response([
            'status' => 'success',
            'message' => 'Question fetched successfully',
            'data' => $question
        ], 200);
    }

    public function show(BasicQuestion $question)
    {
        $question->categories = $this->categories($question->id);
        $question->options = $this->fetch_question_options($question->id);

        return response([
            'status' => 'success',
            'message' => 'Question successfully fetched',
            'data' => $question
        ], 200);
    }

    public function update(UpdateBasicQuestionRequest $request, BasicQuestion $question)
    {
        $question->question = $request->question;
        $caategory_ids = [];
        foreach($request->categories as $category){
            $categ = Category::where('id', $category)->orWhere('category', $category)->first();
            if(!empty($categ)){
                $caategory_ids[] = $categ->id;   
            }
        }
        $question->categories = join(',', $caategory_ids);
        $question->is_k10 = $request->is_k10;
        $question->special_options = $request->special_options;
        $question->save();
        if($question->special_options == 1){
            foreach($request->options as $options){
                if(!empty($options['id'])){
                    $option = BasicQuestionSpecialOption::find($options['id']);
                    $option->option = $options['option'];
                    $option->value = $options['value'];
                    $option->save();
                } else {
                    BasicQuestionSpecialOption::create([
                        'basic_question_id' => $question->id,
                        'option' => $options['option'],
                        'value' => $options['value']
                    ]);
                }
            }
        } else {
            $options = BasicQuestionSpecialOption::where('basic_question_id', $question->id);
            if($options->count() > 0){
                foreach($options->get() as $option){
                    $option->delete();
                }
            }
        }

        $question->categories = $this->categories($question->id);
        $question->options = $this->fetch_question_options($question->id);

        return response([
            'status' => 'success',
            'message' => 'Question updated successfully',
            'data' => $question
        ], 200);
    }

    public function delete_special_option(BasicQuestionSpecialOption $option){
        $option->delete();
        $question = BasicQuestion::find($option->basic_question_id);
        $question->categories = $this->categories($question->id);
        $question->options = $this->fetch_question_options($question->id);

        return response([
            'status' => 'success',
            'message' => 'Option successfully deleted',
            'data' => $question
        ], 200);
    }

    public function destroy(BasicQuestion $question)
    {
        $question->delete();
        if($question->special_options == 1){
            $options = BasicQuestionSpecialOption::where('basic_question_id', $question->id);
            if($options->count() > 0){
                foreach($options->get() as $option){
                    $option->delete();
                }
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Question successfully deleted'
        ], 200);
    }
}
