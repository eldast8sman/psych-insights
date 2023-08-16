<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FileManagerController;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;

class ArticleController extends Controller
{
    private $user;
    private $file_disk = 'public';

    public function __construct()
    {
        $this->middleware('auth:admin-api');
        $this->user = AuthController::user();
    }

    public static function article(Article $article) : Article
    {
        if(!empty($article->photo)){
            $article->photo = FileManagerController::fetch_file($article->photo);
        }

        if(!empty($article->categories)){
            $categories = [];

            $categs = explode(',', $article->categories);
            foreach($categs as $categ){
                $category = Category::find(trim($categ));
                if(!empty($category)){
                    $categories[] = $category->category;
                }
            }

            $article->categories = $categories;
        }

        return $article;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = !empty($_GET['search']) ? $_GET['search'] : "";
        $filter = isset($_GET['status']) ? (int)$_GET['status'] : NULL;
        $sort = !empty($_GET['sort']) ? (string)$_GET['sort'] : 'asc';
        $from = !empty($_GET['from']) ? (string)$_GET['from'] : "";
        $to = !empty($_GET['to']) ? (string)$_GET['to'] : "";
        $sort_by = !empty($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'title';
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $articles = Article::where('status', '>=', 0);
        if(!empty($search)){
            $articles = $articles->where('title', 'like', '%'.$search.'%');
        }
        if($filter !== NULL){
            $articles = $articles->where('status', $filter);
        }
        if(!empty($from)){
            $articles = $articles->where('publication_date', '>=', $from);
        }
        if(!empty($to)){
            $articles = $articles->where('publication_date', '<=', $to);
        }
        if((($sort_by == 'title') || ($sort_by == 'publication_date')) && (($sort == 'asc') || ($sort == 'desc'))){
            $articles = $articles->orderBy($sort_by, $sort);
        }

        if($articles->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Article was fetched',
                'data' => null
            ], 200);
        }

        $articles = $articles->paginate($limit);
        foreach($articles as $article){
            $article = self::article($article);
        }

        return response([
            'status' => 'success',
            'message' => 'Articles fetched successfully',
            'data' => $articles
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        $all = $request->except(['photo', 'categories']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Article Photo could not be uploaded'
                ], 500);
            }

            $all['photo'] = $upload->id;
        }
        $all['categories'] = join(',', $request->categories);
        $all['status'] = 1;

        if(!$article = Article::create($all)){
            if(isset($all['photo']) && !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Article upload failed'
            ], 500);
        }

        $article = self::article($article);

        return response([
            'status' => 'success',
            'message' => 'Article uploaded successfully',
            'data' => $article
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        $article = self::article($article);

        return response([
            'status' => 'success',
            'message' => 'Article successfully fetched',
            'data' => $article
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $old_photo = $article->photo;
        $all = $request->except(['categories', 'photo']);
        if(!empty($request->photo)){
            if(!$upload = FileManagerController::upload_file($request->photo, env('FILE_DISK', $this->file_disk))){
                return response([
                    'status' => 'failed',
                    'message' => 'Article Photo could not be uploaded'
                ], 500);
            }

            $all['photo'] = $upload->id;
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
        if(!$article->update($all)){
            if(isset($all['photo']) && !empty($all['photo'])){
                FileManagerController::delete($all['photo']);
            }
            return response([
                'status' => 'failed',
                'message' => 'Article Update failed'
            ], 500);
        }
        if(!empty($old_photo)){
            FileManagerController::delete($old_photo);
        }

        return response([
            'status' => 'success',
            'message' => 'Article updated successfully',
            'data' => self::article($article) 
        ], 200);
    }

    public function activation(Article $article){
        $article->status = ($article->status == 0) ? 1 : 0;
        $article->save();

        return response([
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => self::article($article)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        $article->delete();
        if(!empty($article->photo)){
            FileManagerController::delete($article->photo);
        }

        return response([
            'status' => 'success',
            'Article successfully deleted'
        ], 200);
    }
}
