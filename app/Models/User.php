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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['follow_status'];

    public function posts(){
        return $this->hasMany(Post::class);
    }

    public function likedPosts(){
        return $this->belongsToMany(Post::class, 'post_likes', 'user_id', 'post_id');
    }

    public function comments(){
        return $this->hasMany(PostComment::class);
    }

    public function followers(){
        return $this->hasMany(UserFollow::class, 'followed_id')->where('status', 'accepted');
    }

    public function following(){
        return $this->hasMany(UserFollow::class, 'follower_id')->where('status', 'accepted');
    }

    public function followRequests(){
        return $this->hasMany(UserFollow::class, 'followed_id')->where('status', 'pending');
    }

    public function getFollowStatusAttribute(){
        $follow = UserFollow::where('follower_id', Auth::user()->id)->where('followed_id', $this->id)->first();
        if($follow) return $follow->status;
        return null;
    }
}
