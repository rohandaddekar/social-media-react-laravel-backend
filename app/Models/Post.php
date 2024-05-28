<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'images',
        'user_id',
        'publish_at',
        'is_published',
    ];

    protected $appends = ['is_liked', 'is_owner'];

    public function getIsLikedAttribute()
    {
        $user = Auth::user();
        if ($user) {
            return $user->likedPosts()->where('post_id', $this->id)->exists();
        }
        return false;
    }

    public function getIsOwnerAttribute()
    {
        $user = Auth::user();
        return $user ? $this->user_id === $user->id : false;
    }


    public function user(){
        return $this->belongsTo(User::class);
    }

    public function likes(){
        return $this->hasMany(PostLike::class);
    }

    public function comments(){
        return $this->hasMany(PostComment::class);
    }
}
