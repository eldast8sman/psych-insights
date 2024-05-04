<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatGPTController extends Controller
{
    private $key;
    public function __construct()
    {
        $this->key = env('CHAT_GPT_KEY');
    }

    public function welcome_message($name, $diagnosis){
        $client = new Client();

        $messages = [
            ['role' => 'system', 'content' => 'You are ChatGPT, a mental health therapist.'],
            ['role' => 'user', 'content' => "Create a welcome message for '{$name}' on the Psych Insights App after the App has analysed that he/she is likely to be '{$diagnosis}' telling him/her that we are happy to have them on the PsychInsights App and they can browse through the recommended resources which includes Mental Health Strategies, Articles, Podcasts, Audio, Videos and Books, which have been curated just for him/her based on our analysis of his/her mental health condition to help his/her mental well being. Please note that this shold be in not more than 150 words, the message should be in Australian English and it should be divided into paragraphs"],
            ['role' => 'assistant', 'content' => ''], // Empty content for GPT to fill
        ];

        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->key,
            ],
            'json' => [
                'model' => "gpt-3.5-turbo",
                'messages' => $messages,
            ],
        ]);

        $return = json_decode($response->getBody()->getContents());
        $choices = $return->choices;
        $choice = array_shift($choices);
        return $choice->message->content;
    }

    public function chat(ChatRequest $request){        
        $client = new Client();
        $messages = [
            ['role' => 'system', 'content' => 'You are Sarah Tonin, a mental health therapist.']
        ];

       foreach($request->prompt as $req){
            $messages[] = [
                'role' => $req['role'],
                'content' => $req['content']
            ];
        }
        $messages[] = ['role' => 'assistant', 'content' => ''];

        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->key,
            ],
            'json' => [
                'model' => "gpt-3.5-turbo",
                'messages' => $messages,
            ],
        ]);

        $return = json_decode($response->getBody()->getContents());
        $choices = $return->choices;
        $choice = array_shift($choices);
        $response = $choice->message->content;


        return response([
            'status' => 'success',
            'data' => $response
        ], 200);
    }

    public function complete_chat($prompt){
        $client = new Client();

        $messages = [
            ['role' => 'system', 'content' => 'You are ChatGPT, a mental health therapist.'],
            ['role' => 'user', 'content' => "Create a welcome message for Tola whose Mental Diagnosis is '{$prompt}' telling him/her that we are happy to have them on the App and they can browse through the recommended resources to helo their mental well being"],
            ['role' => 'assistant', 'content' => ''], // Empty content for GPT to fill
        ];

        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->key,
            ],
            'json' => [
                'model' => "gpt-3.5-turbo",
                'messages' => $messages,
            ],
        ]);

        $return = $response->getBody()->getContents();

        return response([
            'status' => 'success',
            'message' => json_decode($return)
        ], 200);
    }
}
