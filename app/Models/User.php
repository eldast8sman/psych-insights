<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'token',
        'token_expiry',
        'verification_token',
        'verification_token_expiry',
        'email_verified',
        'profile_photo',
        'interests',
        'daily_quote_id',
        'daily_tip_id',
        'last_login',
        'prev_login',
        'user_guide',
        'status',
        'deactivated',
        'last_login_date',
        'present_streak',
        'longest_streak',
        'total_logins',
        'resources_completed',
        'goals_completed',
        'last_country',
        'last_ip',
        'last_timezone',
        'signup_country',
        'signup_ip',
        'signup_timezone',
        'device_token',
        'web_token',
        'app_account_token',
        'next_daily_question',
        'next_assessment'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'token',
        'token_expiry',
        'verification_token',
        'verification_token_expiry',
        'device_token',
        'web_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'last_login' => 'datetime',
        'prev_login' => 'datetime'
    ];

    public function subscription_histories(){
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function current_subscription(){
        return $this->hasOne(CurrentSubscription::class);
    }
}
