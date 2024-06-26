<?php

namespace App\Http\Controllers;

use App\Events\PostCommentEvent;
use App\Http\Requests\PostCommentStoreRequest;
use App\Models\Post;
use App\Models\PostComment;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostCommentController extends Controller
{
    use ApiResponse, Notification;

    /**
     * Display a listing of the resource.
     */
    public function index($post_id)
    {
        try {
            $comments = PostComment::with(['user:id,first_name,last_name,email,profile_image'])
                                    ->where('post_id', $post_id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

            return $this->successResponse('all comment fetched successfully', $comments, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch all comment', $this->formatException($e), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostCommentStoreRequest $request, $post_id)
    {
        DB::beginTransaction();

        try {
            $request->validated();

            $post = Post::find($post_id);
            if(!$post) return $this->errorResponse('post not found', null, 404);

            $comment = PostComment::create([
                'comment' => $request->comment,
                'post_id' => $post->id,
                'user_id' => Auth::user()->id
            ]);

            $comment->load(['user:id,first_name,last_name,email,profile_image', 'post.user:id,first_name,last_name,email,profile_image']);

            PostCommentEvent::dispatch($comment, 'created');

            if($comment->post->user_id !== Auth::user()->id) {
                $this->createAndDispatchNotification('post', [
                    'message' => $comment->user->first_name . " " . $comment->user->last_name . ' added a comment to your post.',
                    'user' => $comment->user,
                    'post_id' => $comment->post_id
                ], $comment->post->user_id);
            }

            DB::commit();

            return $this->successResponse('comment added successfully', $comment, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to add comment', $this->formatException($e), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $comment = PostComment::with([
                        'user:id,first_name,last_name,email,profile_image',
                        'post.user:id,first_name,last_name,email,profile_image',
                    ])->find($id);
            if (!$comment) {
                return $this->errorResponse('comment not found', null, 404);
            }

            return $this->successResponse('comment fetched successfully', $comment, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch comment', $this->formatException($e), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $comment = PostComment::find($id);
            if (!$comment) {
                return $this->errorResponse('comment not found', null, 404);
            }

            $comment->fill($request->only(['comment']));
            $comment->save();

            $comment->load('user:id,first_name,last_name,email,profile_image');
            PostCommentEvent::dispatch($comment, 'updated');

            DB::commit();

            return $this->successResponse('comment updated successfully', $comment, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to update comment', $this->formatException($e), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $comment = PostComment::find($id);
            if (!$comment) {
                return $this->errorResponse('comment not found', null, 404);
            }

            PostCommentEvent::dispatch($comment, 'deleted');
            $comment->delete();

            DB::commit();

            return $this->successResponse('comment deleted successfully', $comment, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to delete comment', $this->formatException($e), 500);
        }
    }
}
