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
            $per_page = $request->per_page ?? 10;
            $user_id = $request->user_id ?? null;
            $scheduled = $request->scheduled ?? null;

            $query = Post::query();

            if($user_id) {
                $query->where('user_id', $user_id);
            }

            if($scheduled) {
                $query->where('is_published', false)->where('user_id', Auth::user()->id);
            } else {
                $query->where('is_published', true);
            }

            $query->with([
                        'user:id,first_name,last_name,email,profile_image,about_me', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])
                    ->orderBy('publish_at', $scheduled ? 'asc' : 'desc');

            $posts = $query->paginate($per_page);

            foreach ($posts as $post) {
                $post->images = json_decode($post->images);
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

            $publishAt = $request->publish_at ?? now();
            $isPublished = !$request->has('publish_at') || $request->publish_at <= now();

            $post = Post::create([
                'content' => $request->content,
                'images' => json_encode($uploadedImages),
                'user_id' => $user->id,
                'publish_at' => $publishAt,
                'is_published' => $isPublished,
            ]);

            $post->load([
                'user:id,first_name,last_name,email,profile_image',
                'likes.user:id,first_name,last_name,email,profile_image',
                'comments.user:id,first_name,last_name,email,profile_image'
            ]);
    
            $post->images = json_decode($post->images);

            if($post->is_published) {
                PostEvent::dispatch($post, 'created');
            }

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
            $post = Post::with([
                        'user:id,first_name,last_name,email,profile_image', 
                        'likes.user:id,first_name,last_name,email,profile_image', 
                        'comments.user:id,first_name,last_name,email,profile_image'
                    ])->find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            $post->images = json_decode($post->images);

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

            $publishAt = $request->publish_at ?? now();
            $isPublished = !$request->has('publish_at') || $request->publish_at <= now();

            $post->fill([
                'content' => $request->content,
                'publish_at' => $publishAt,
                'is_published' => $isPublished,
            ]);

            $post->save();
            
            $post->load([
                'user:id,first_name,last_name,email,profile_image',
                'likes.user:id,first_name,last_name,email,profile_image',
                'comments.user:id,first_name,last_name,email,profile_image'
            ]);
            $post->images = json_decode($post->images);

            PostEvent::dispatch($post, 'updated');

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
        DB::beginTransaction();

        try {
            $post = Post::find($id);
            if (!$post) {
                return $this->errorResponse('post not found', null, 404);
            }

            PostEvent::dispatch($post, 'deleted');

            $post->delete();

            DB::commit();

            return $this->successResponse('post deleted successfully', $post, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to delete post', $this->formatException($e), 500);
        }
    }
}
