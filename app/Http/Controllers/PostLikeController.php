<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostLikeController extends Controller
{
    use ApiResponse;

    public function likeUnlike(Request $request, $post_id){
        try {
            $post = Post::find($post_id);
            if(!$post) return $this->errorResponse('post not found', null, 404);

            $result = Auth::user()->likedPosts()->toggle($post_id);
            $action = empty($result['attached']) ? 'unliked' : 'liked';

            return $this->successResponse("successfully {$action} post", null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse("failed to toggle post like", $this->formatException($e), 500);
        }
    }
}
