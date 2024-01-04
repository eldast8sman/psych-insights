<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Audio;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\UserCategoryLog;
use App\Models\FavouriteResource;
use App\Models\TempAnswer;
use App\Models\UserIPAddress;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public static function log_activity($user_id, $activity, $activity_model=null, $activity_id=null)
    {
        ActivityLog::create([
            'user_id' => $user_id,
            'activity' => $activity,
            'activity_model' => $activity_model,
            'activity_id' => $activity_id
        ]);
    }

    public static function paginate_array($array, $per_page=10, $page=null, $options=[]){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $page = intval($page);
        $per_page = intval($per_page);
        $items = $array instanceof Collection ? $array : Collection::make($array);
        $results = $items->slice(($page - 1) * $per_page, $per_page)->values();
        return new LengthAwarePaginator($results, $items->count(), $per_page, $page, $options);
    }

    public static function favourite_resource($type, $user_id, $resource_id){
        $resource = FavouriteResource::where('type', $type)->where('user_id', $user_id)->where('resource_id', $resource_id)->first();
        if(empty($resource)){
            FavouriteResource::create([
                'user_id' => $user_id,
                'type' => $type,
                'resource_id' => $resource_id
            ]);

            return "saved";
        } else {
            $resource->delete();
            return "deleted";
        }
    }

    public static function category_log($user_id, $categories=[]) : void
    {
        if(!empty($categories)){
            foreach($categories as $categ){
                $category = Category::where('category', $categ)->first();
                $usercateg = UserCategoryLog::where('user_id', $user_id)->where('category_id', $category->id)->where('day', date('Y-m-d'));
                if($usercateg->count() < 1){
                    UserCategoryLog::create([
                        'user_id' => $user_id,
                        'category_id' => $category->id,
                        'count' => 1,
                        'day' => date('Y-m-d')
                    ]);
                } else {
                    $usercateg = $usercateg->first();
                    $usercateg->count += 1;
                    $usercateg->save();
                }
            }
        }
    }

    public static function complete_resource($user_id) : void
    {
        $user = User::find($user_id);
        $user->resources_completed += 1;
        $user->save();
    }

    public static function check_ip(Request $request, $user_id){
        $ip_address = $request->ip();       

        if(!empty($ip_address)){
            $user = User::find($user_id);
            $address = UserIPAddress::where('user_id', $user_id)->where('ip_address', $ip_address)->first();
            if(!empty($address)){
                $address->frequency += 1;
                $address->save();

                $user->last_country = $address->country;
                if(empty($user->signup_country)){
                    $user->signup_country = $user->last_country;
                }
                $user->save();
            } else {
                $position = Location::get();
                if($position){
                    $address = UserIPAddress::create([
                        'user_id' => $user_id,
                        'ip_address' => $ip_address,
                        'country' => $position->countryName,
                        'location_details' => json_encode($position),
                        'frequency' => 1
                    ]);

                    $user->last_country = $address->country;
                    if(empty($user->signup_country)){
                        $user->signup_country = $user->last_country;
                    }
                    $user->save();
                }
            }
        }
    }

    public static function temp_answer($user_id, $type, $answers) : void
    {
        $temp_answer = TempAnswer::where('user_id', $user_id)->first();
        if(empty($temp_answer)){
            $temp_answer =  TempAnswer::create([
                'user_id' => $user_id,
                'question_type' => $type,
                'answers' => json_encode($answers)
            ]);
        } else {
            $temp_answer->question_type = $type;
            $temp_answer->answers = json_encode($answers);
            $temp_answer->save();
        }
    }

    public static function delete_temp_answer($user_id) : void
    {
        $temp = TempAnswer::where('user_id', $user_id)->first();
        if(!empty($temp)){
            $temp->delete();
        }
    }

    public static function fetch_temp_answer($user_id){
        $temp = TempAnswer::where('user_id', $user_id)->first(['question_type', 'answers']);
        if(!empty($temp)){
            $temp->answers = json_decode($temp->answers);
        }

        return $temp;
    }

    public static function fetch_temp_answer_by_type($user_id, $type){
        $temp = TempAnswer::where('user_id', $user_id)->where('question_type', $type)->first(['question_type', 'answers']);
        if(!empty($temp)){
            $temp->answers = json_decode($temp->answers);
        }

        return $temp;
    }
}
