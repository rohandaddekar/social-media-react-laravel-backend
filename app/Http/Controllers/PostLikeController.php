<?php

namespace App\Http\Controllers;

use App\Events\PostLikeEvent;
use App\Models\Post;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostLikeController extends Controller
{
    use ApiResponse, Notification;

    public function likeUnlike(Request $request, $post_id){
        DB::beginTransaction();

        try {
            $post = Post::find($post_id);
            if(!$post) return $this->errorResponse('post not found', null, 404);

            $user = Auth::user();
            $result = $user->likedPosts()->toggle($post_id);
            $action = empty($result['attached']) ? 'unliked' : 'liked';

            $post->load(['likes.user:id,first_name,last_name,email,profile_image']);

            if($post->user_id !== $user->id) {
                $this->createAndDispatchNotification('post', [
                    'message' => $user->first_name . " " . $user->last_name . " has " . $action . " your post.",
                    'user' => $user,
                    'post_id' => $post->id
                ], $post->user_id);
            }

            PostLikeEvent::dispatch($post);

            DB::commit();

            return $this->successResponse("successfully {$action} post", $result, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse("failed to toggle post like", $this->formatException($e), 500);
        }
    }
}
