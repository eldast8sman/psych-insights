<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserIPAddress;
use App\Services\IpApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IpAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $ip_address;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $ip_address, $type="login")
    {
        $this->ip_address = $ip_address;
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;
        $ip_details = UserIPAddress::where('ip_address', $this->ip_address)->where('platform', 'IP-API')->first();
        if(!empty($ip_details) and !empty($ip_details->location_details)){
            $loc_details = json_decode($ip_details->location_details);
        } else {
            $service = new IpApiService();
            $details = $service->ip_data($this->ip_address);
            if($details->ok()){
                $details = $details->object();
                if($details->status == "success"){
                    $loc_details = $details;
                }
            }
        }
        if(isset($loc_details)){
            if(empty($user->signup_country)){
                $user->signup_country = $loc_details->country;
                $user->signup_timezone = $loc_details->timezone;
                $user->signup_ip = $this->ip_address;
            }
            $user->last_country = $loc_details->country;
            $user->last_timezone = $loc_details->timezone;
            $user->last_ip = $this->ip_address;
            $user->save();

            $address = UserIPAddress::where('user_id', $user->id)->where('ip_address', $this->ip_address)->where('platform', 'IP-API')->first();
            if(!empty($address)){
                $address->frequency += 1;
                $address->save();
            } else {
                UserIPAddress::create([
                    'user_id' => $user->id,
                    'platform' => 'IP-API',
                    'ip_address' => $this->ip_address,
                    'country' => $loc_details->country,
                    'location_details' => json_encode($loc_details),
                    'frequency' => 1
                ]);
            }
        }
    }
}
