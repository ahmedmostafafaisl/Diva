<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Event;
use Illuminate\Support\Str;
use App\Models\UserWorkSchedule;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    protected array $guard_name = ['sanctum'];

    protected $fillable = [
        'username',
        'type',
        'email',
        'phone',
        'password',
        'fcm_token',
        'second_phone',
        'city_id',
        'is_verified',
        'following_id',
        'notification_id',
        'otp',
        'dy_id',
        'dy_integrated',
        'status',
        'role',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'is_private',
         'dashboard_id',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',

    ];

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function data()
    {
        return $this->hasOne(UserData::class, 'user_id');
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }


    public function cart()
    {
        return $this->hasOne(Cart::class);
    }


    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_users', 'user_id', 'coupon_id')
            ->withPivot('is_used')
            ->withTimestamps();
    }


    public function addresses()
    {
        return $this->hasMany(Address2::class);
    }




    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Lists
    public function lists()
    {
        return $this->hasMany(MyList::class);
    }

    // Waiting List
    public function waitingList()
    {
        return $this->hasOne(WaitingList::class);
    }

    // new lists

    public function productLists()
    {
        return $this->hasMany(UserProductList::class);
    }

    // follow requests
    public function followRequestsSent()
    {
        return $this->hasMany(FollowRequest::class, 'follower_id');
    }

    public function followRequestsReceived()
    {
        return $this->hasMany(FollowRequest::class, 'followed_id');
    }
    // chat
    public function chatRooms()
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
