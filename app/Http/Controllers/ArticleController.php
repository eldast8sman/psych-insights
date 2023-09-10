<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\OpenedArticle;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedArticle;

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
            if(($counted < $limit) && (Article::where('status', 1)->where('subscription_level', '<=', $level)->count() >= $limit)){
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
}
