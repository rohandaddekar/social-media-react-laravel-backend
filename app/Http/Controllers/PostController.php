<?php

namespace App\Http\Controllers;

use App\Events\PostEvent;
use App\Http\Requests\PostStoreRequest;
use App\Models\Post;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use ApiResponse, FileUpload;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $posts = Post::with([
                        'user:id,first_name,last_name,email,profile_image', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->get();

            return $this->successResponse('all posts fetched successfully', $posts, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch all posts', $this->formatException($e), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostStoreRequest $request)
    {
        try {
            $request->validated();

            $user = Auth::user();

            $uploadedImages = [];
            if($request->has('images')) {
                $uploadedImages = $this->fileUpload($request->images);
            }

            $post = Post::create([
                'content' => $request->content,
                'images' => json_encode($uploadedImages),
                'user_id' => $user->id,
            ]);
            $post->images = json_decode($post->images);

            PostEvent::dispatch($post);

            return $this->successResponse('post created successfully', $post, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to create post', $this->formatException($e), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $post = Post::with([
                        'user:id,first_name,last_name,email,profile_image', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])->find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            return $this->successResponse('post fetched successfully', $post, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch post', $this->formatException($e), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $post = Post::find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            $post->fill($request->only(['content', 'images']));
            $post->save();

            return $this->successResponse('post updated successfully', $post, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to update post', $this->formatException($e), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $post = Post::find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            $post->delete();

            return $this->successResponse('post deleted successfully', $post, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to delete post', $this->formatException($e), 500);
        }
    }
}
