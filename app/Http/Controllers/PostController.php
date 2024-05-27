<?php

namespace App\Http\Controllers;

use App\Events\PostEvent;
use App\Http\Requests\PostStoreRequest;
use App\Models\Post;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    use ApiResponse, FileUpload;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $authUser = Auth::user();
            $per_page = $request->per_page ?? 10;

            $posts = Post::with([
                        'user:id,first_name,last_name,email,profile_image', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->paginate($per_page);

            $likedPostIds = $authUser ? $authUser->likedPosts()->pluck('post_id')->toArray() : [];

            foreach ($posts as $post) {
                $post->images = json_decode($post->images);
                $post->is_liked = in_array($post->id, $likedPostIds);
                $post->is_owner = $authUser ? $post->user_id === $authUser->id : false;
            }

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
        DB::beginTransaction();

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

            $post->load([
                'user:id,first_name,last_name,email,profile_image',
                'likes.user:id,first_name,last_name,email,profile_image',
                'comments.user:id,first_name,last_name,email,profile_image'
            ]);
    
            $post->images = json_decode($post->images);
            $likedPostIds = $user ? $user->likedPosts()->pluck('post_id')->toArray() : [];
            $post->is_liked = in_array($post->id, $likedPostIds);
            $post->is_owner = $user ? $post->user_id === $user->id : false;

            PostEvent::dispatch($post);

            DB::commit();

            return $this->successResponse('post created successfully', $post, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to create post', $this->formatException($e), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $authUser = Auth::user();
            $post = Post::with([
                        'user:id,first_name,last_name,email,profile_image', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])->find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            $likedPostIds = $authUser ? $authUser->likedPosts()->pluck('post_id')->toArray() : [];

            $post->images = json_decode($post->images);
            $post->is_liked = in_array($post->id, $likedPostIds);

            return $this->successResponse('post fetched successfully', $post, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch post', $this->formatException($e), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostStoreRequest $request, string $id)
    {
        DB::beginTransaction();

        try {
            $request->validated();

            $post = Post::find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            $oldImages = [];
            if ($request->has('old_images')) {
                $oldImages = $request->old_images;
            }

            $newUploadedImages = [];
            if($request->has('images')) {
                $newUploadedImages = $this->fileUpload($request->images);
            }

            $mergedImages = array_merge($oldImages, $newUploadedImages);
            $post->images = !empty($mergedImages) ? json_encode($mergedImages) : json_encode([]);

            $post->fill($request->only(['content']));
            $post->save();

            DB::commit();

            return $this->successResponse('post updated successfully', $post, 200);
        } catch (\Exception $e) {
            DB::rollBack();
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

            $post->images = json_decode($post->images);

            return $this->successResponse('post deleted successfully', $post, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to delete post', $this->formatException($e), 500);
        }
    }
}
