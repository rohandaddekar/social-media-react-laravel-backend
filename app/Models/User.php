<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'profile_image',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['follow_status'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFollowStatusAttribute() {
        $authUser = Auth::user();
        if (!$authUser) {
            return 'none';
        }

        $sentRequest = $this->sentFollowRequests()->where('receiver_id', $authUser->id)->first();
        if ($sentRequest) {
            if ($sentRequest->status === 'pending') {
                return 'pending_received';
            } elseif ($sentRequest->status === 'accepted') {
                return 'following';
            }
        }

        $receivedRequest = $this->receivedFollowRequests()->where('sender_id', $authUser->id)->first();
        if ($receivedRequest) {
            if ($receivedRequest->status === 'pending') {
                return 'pending_sent';
            } elseif ($receivedRequest->status === 'accepted') {
                return 'follower';
            }
        }

        return 'none';
    }

    public function posts(){
        return $this->hasMany(Post::class);
    }

    public function likedPosts(){
        return $this->belongsToMany(Post::class, 'post_likes', 'user_id', 'post_id');
    }

    public function comments(){
        return $this->hasMany(PostComment::class);
    }

    public function sentFollowRequests(){
        return $this->hasMany(UserFollow::class, 'sender_id');
    }

    public function receivedFollowRequests(){
        return $this->hasMany(UserFollow::class, 'receiver_id');
    }

    public function notifications(){
        return $this->hasMany(Notification::class);
    }
}
