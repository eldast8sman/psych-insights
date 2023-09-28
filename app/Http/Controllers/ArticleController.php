<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\OpenedArticle;
use App\Models\OpenedResources;
use App\Models\FavouriteResource;
use App\Models\RecommendedArticle;
use App\Models\CurrentSubscription;

class ArticleController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api', ['except' => ['recommend_articles']]);
        $this->user = AuthController::user();
    }

    public static function recommend_articles($limit, $user_id, $cat_id, $level=0){
        $rec_articles = RecommendedArticle::where('user_id', $user_id);
        if($rec_articles->count() > 0){
            foreach($rec_articles->get() as $rec_article){
                $rec_article->delete();
            }
        }

        if($limit > 0){
            $opened_articles = [];
            $op_articles = OpenedArticle::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_articles->count() > 0){
                foreach($op_articles->get() as $op_article){
                    $opened_articles[] = $op_article->article_id;
                }
            }

            $articles_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $articles = Article::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($articles as $article){
                if(count($articles_id) < $first_limit){
                    $categories = explode(',', $article->categories);
                    if(in_array($cat_id, $categories) and !in_array($article->id, $opened_articles)){
                        $articles_id[] = $article;
                        $ids[] = $article->id;
                    }
                } else {
                    break;
                }
            }



            if(count($articles_id) < $first_limit){
                foreach($opened_articles as $opened_article){
                    if(count($articles_id) < $first_limit){
                        $article = Article::find($opened_article);
                        if(!empty($article) && ($article->status == 1) && ($article->subscription_level <= $level)){
                            $categories = explode(',', $article->categories);
                            if(in_array($cat_id, $categories)){
                                $articles_id[] = $article;
                                $ids[] = $article->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($articles_id);
            if(($counted < $limit) && (Article::where('status', 1)->where('subscription_level', '<=', $level)->count() > $counted)){
                $other_articles = Article::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($articles_id)){
                    foreach($articles_id as $article_id){
                        $other_articles = $other_articles->where('id', '<>', $article_id->id);
                    }
                }
                $other_articles = $other_articles->inRandomOrder();
                if($other_articles->count() > 0){
                    $other_articles = $other_articles->get(['id', 'slug']);
                    foreach($other_articles as $other_article){
                        if(count($articles_id) < $limit){
                            if(!in_array($other_article->id, $opened_articles)){
                                $articles_id[] = $other_article;
                                $ids[] = $other_article->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($articles_id) < $limit){
                        foreach($other_articles as $other_article){
                            if(count($articles_id) < $limit){
                                if(!in_array($other_article->id, $ids)){
                                    $articles_id[] = $other_article;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($articles_id)){
                foreach($articles_id as $article){
                    RecommendedArticle::create([
                        'user_id' => $user_id,
                        'article_id' => $article->id,
                        'slug' => $article->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }

    public static function fetch_article(Article $article) : Article
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

        unset($article->created_at);
        unset($article->updated_at);
        unset($article->id);

        return $article;
    }

    public function recommended_articles(){
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $rec_articles = RecommendedArticle::where('user_id', $this->user->id);
        if($rec_articles->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Recommended Article',
                'data' => []
            ], 200);
        }

        $articles = [];
        $rec_articles = $rec_articles->get();
        if(empty($search)){
            foreach($rec_articles as $rec_article){
                $article = Article::find($rec_article->article_id);
                if(!empty($article) && ($article->status == 1)){
                    $articles[] = $this->fetch_article($article);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($rec_articles as $rec_article){
                $article = Article::find($rec_article->article_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($article->title, $word) !== FALSE) or (strpos($article->summary, $word) !== FALSE) or (strpos($article->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$article->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $article = Article::find($key);
                    if(!empty($article) and ($article->status == 1)){
                        $articles[] = $this->fetch_article($article);
                    }
                }
            }
        }

        if(empty($articles)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Articles fetched successfully',
            'data' => self::paginate_array($articles, $limit, $page)
        ], 200);
    }

    public function recommended_article($slug){
        $rec_article = RecommendedArticle::where('user_id', $this->user->id)->where('slug', $slug)->first();
        if(empty($rec_article)){
            return response([
                'status' => 'failed',
                'message' => 'No Book was fetched'
            ], 404);
        }

        $article = Article::find($rec_article->article_id);
        if(empty($article) or ($article->status != 1)){
            return response([
                'ststus' => 'failed',
                'message' => 'No Article was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Article fetched successfully',
            'data' => $this->fetch_article($article)
        ], 200);
    }

    public function mark_as_opened($slug){
        $article = Article::where('slug', $slug)->first();
        if(empty($article)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was fetched'
            ], 404);
        }

        $opened = OpenedArticle::where('user_id', $this->user->id)->where('article_id', $article->id)->first();
        if(empty($opened)){
            OpenedArticle::create([
                'user_id' => $this->user->id,
                'article_id' => $article->id,
                'frequency' => 1
            ]);
        } else {
            $opened->frequency += 1;
            $opened->save();
        }

        return response([
            'status' => 'success',
            'message' => 'Marked as Opened'
        ], 200);
    }

    public function opened_articles(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }
        
        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $opened_articles = OpenedArticle::where('user_id', $this->user->id);
        if($opened_articles->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Opened Article',
                'data' => []
            ], 200);
        }

        $articles = [];
        $opened_articles = $opened_articles->get();
        if(empty($search)){
            foreach($opened_articles as $opened_article){
                $article = Article::find($opened_article->article_id);
                if(!empty($article) && ($article->status == 1)){
                    $articles[] = $this->fetch_article($article);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($opened_articles as $opened_article){
                $article = Article::find($opened_article->article_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($article->title, $word) !== FALSE) or (strpos($article->summary, $word) !== FALSE) or (strpos($article->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$article->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $article = Article::find($key);
                    if(!empty($article) and ($article->status == 1)){
                        $articles[] = $this->fetch_article($article);
                    }
                }
            }
        }

        if(empty($articles)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Articles fetched successfully',
            'data' => self::paginate_array($articles, $limit, $page)
        ], 200);
    }

    public function opened_article($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $article = Article::where('slug', $slug)->first();
        if(empty($article) or ($article->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was fetched'
            ], 404);
        }

        $opened = OpenedArticle::where('article_id', $article->id)->where('user_id', $this->user->id)->first();
        if(empty($opened)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Article fetched successfully',
            'data' => $this->fetch_article($article)
        ], 200);
    }

    public function article_favourite($slug){
        $article = Article::where('slug', $slug)->first();
        if(empty($article)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was fetched'
            ], 404);
        }

        $action = self::favourite_resource('article', $this->user->id, $article->id);
        $message = ($action == 'saved') ? 'Article added to Favourites' : 'Article removed from Favourites';

        return response([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    public function favourite_articles(){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $search = !empty($_GET['search']) ? (string)$_GET['search'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $fav_articles = FavouriteResource::where('type', 'article')->where('user_id', $this->user->id);
        if($fav_articles->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Favourite Article',
                'data' => []
            ], 200);
        }

        $articles = [];
        $fav_articles = $fav_articles->get();
        if(empty($search)){
            foreach($fav_articles as $fav_article){
                $article = Article::find($fav_article->resource_id);
                if(!empty($article) && ($article->status == 1)){
                    $articles[] = $this->fetch_article($article);
                }
            }
        } else {
            $recommendeds = [];
            $search_array = explode(' ', $search);
            foreach($fav_articles as $fav_article){
                $article = Article::find($fav_article->resource_id);
                $count = 0;
                foreach($search_array as $word){
                    if((strpos($article->title, $word) !== FALSE) or (strpos($article->summary, $word) !== FALSE) or (strpos($article->author, $word) !== FALSE)){
                        $count += 1;
                    }
                }
                if($count > 0){
                    $recommendeds[$article->id] = $count;
                }
            }
            if(!empty($recommendeds)){
                arsort($recommendeds);

                $keys = array_keys($recommendeds);

                foreach($keys as $key){
                    $article = Article::find($key);
                    if(!empty($article) and ($article->status == 1)){
                        $articles[] = $this->fetch_article($article);
                    }
                }
            }
        }

        if(empty($articles)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was found',
                'data' => []
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Articles fetched successfully',
            'data' => self::paginate_array($articles, $limit, $page)
        ], 200);
    }

    public function favourite_article($slug){
        $current_subscription = CurrentSubscription::where('user_id', $this->user->id)->where('grace_end', '>=', date('Y-m-d'))->where('status', 1)->first();
        if(empty($current_subscription)){
            return response([
                'status' => 'failed',
                'message' => 'Unauthorized Access'
            ], 409);
        }

        $article = Article::where('slug', $slug)->first();
        if(empty($article) or ($article->status != 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was fetched'
            ], 404);
        }

        $fav_article = FavouriteResource::where('resource_id', $article->id)->where('user_id', $this->user->id)->where('type', 'article')->first();
        if(empty($fav_article)){
            return response([
                'status' => 'failed',
                'message' => 'No Article was found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Article fetched successfully',
            'data' => $this->fetch_article($article)
        ], 200);
    }
}
