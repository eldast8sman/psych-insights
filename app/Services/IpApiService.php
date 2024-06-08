<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpApiService
{
    private $base_url;

    public function __construct()
    {
        $this->base_url = env('IPAPI_BASE_URL');
    }

    public function ip_data($ip_address){
        $response = Http::get($this->base_url.'/'.$ip_address);
        return $response;
    }
}