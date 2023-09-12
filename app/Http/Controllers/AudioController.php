<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\OpenedAudio;
use Illuminate\Http\Request;
use App\Models\OpenedResources;
use App\Models\RecommendedAudio;

class AudioController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function recommend_audios($limit, $user_id, $cat_id, $level=0){
        $rec_audios = RecommendedAudio::where('user_id', $user_id);
        if($rec_audios->count() > 0){
            foreach($rec_audios->get() as $rec_audio){
                $rec_audio->delete();
            }
        }

        if($limit > 0){
            $opened_audios = [];
            $op_audios = OpenedAudio::where('user_id', $user_id)->orderBy('frequency', 'asc')->orderBy('updated_at', 'asc');
            if($op_audios->count() > 0){
                foreach($op_audios->get() as $op_audio){
                    $opened_audios[] = $op_audio->audio_id;
                }
            }

            $audios_id = [];

            $ids = [];

            $first_limit = round(0.7 * $limit);

            $audios = Audio::where('status', 1)->where('subscription_level', '<=', $level)->orderBy('created_at', 'asc')->get(['id', 'slug', 'categories']);
            foreach($audios as $audio){
                if(count($audios_id) < $first_limit){
                    $categories = explode(',', $audio->categories);
                    if(in_array($cat_id, $categories) and !in_array($audio->id, $opened_audios)){
                        $audios_id[] = $audio;
                        $ids[] = $audio->id;
                    }
                } else {
                    break;
                }
            }

            if(count($audios_id) < $first_limit){
                foreach($opened_audios as $opened_audio){
                    if(count($audios_id) < $first_limit){
                        $audio = Audio::find($opened_audio);
                        if(!empty($audio) && ($audio->status == 1) && ($audio->subscription_level <= $level)){
                            $categories = explode(',', $audio->categories);
                            if(in_array($cat_id, $categories)){
                                $audios_id[] = $audio;
                                $ids[] = $audio->id;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }

            $counted = count($audios_id);
            if(($counted < $limit) && (Audio::where('status', 1)->where('subscription_level', '<=', $level)->count() >= $limit)){
                $other_audios = Audio::where('status', 1)->where('subscription_level', '<=', $level);
                if(!empty($audios_id)){
                    foreach($audios_id as $audio_id){
                        $other_audios = $other_audios->where('id', '<>', $audio_id->id);
                    }
                }
                $other_audios = $other_audios->inRandomOrder();
                if($other_audios->count() > 0){
                    $other_audios = $other_audios->get(['id', 'slug']);
                    foreach($other_audios as $other_audio){
                        if(count($audios_id) < $limit){
                            if(!in_array($other_audio->id, $opened_audios)){
                                $audios_id[] = $other_audio;
                                $ids[] = $other_audio->id;
                            }
                        } else {
                            break;
                        }
                    }
                    if(count($audios_id) < $limit){
                        foreach($other_audios as $other_audio){
                            if(count($audios_id) < $limit){
                                if(!in_array($other_audio->id, $ids)){
                                    $audios_id[] = $other_audio;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            if(!empty($audios_id)){
                foreach($audios_id as $audio){
                    RecommendedAudio::create([
                        'user_id' => $user_id,
                        'audio_id' => $audio->id,
                        'slug' => $audio->slug,
                        'opened' => 0
                    ]);
                }
            }
        }

        return true;
    }
}
