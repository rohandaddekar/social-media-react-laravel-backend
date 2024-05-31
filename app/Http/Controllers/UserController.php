<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * get list of all users except logged in user
     */
    public function index(){
        try {
            $authUser = Auth::user();
            $users = User::where('id', '!=', $authUser->id)
                            ->whereNotNull('email_verified_at')
                            ->where('is_active', true)
                            ->get();
    
            return $this->successResponse('Successfully fetched all users', $users, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetche all users', $this->formatException($e), 500); 
        }
    }

    /**
     * get single user
     */
    public function show(String $id){
        try {
            $user = User::with([
                            'posts' => function ($query) {
                                $query->with([
                                    'user:id,first_name,last_name,email,profile_image', 
                                    'likes.user:id,first_name,last_name,email,profile_image', 
                                    'comments.user:id,first_name,last_name,email,profile_image'
                                ]);
                            }
                        ])->find($id);
            if(!$user) {
                return $this->errorResponse('user not found', null, 404);
            }

            foreach ($user->posts as $post) {
                $post->images = json_decode($post->images);
            }

            return $this->successResponse('successfully fetched user details', $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch user details', $this->formatException($e), 500); 
        }
    }

    /**
     * get logged in user details
     */
    public function me(){
        try {
            $user = Auth::user();

            return $this->successResponse('successfully fetched user details', $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetched user details', $this->formatException($e), 500); 
        }
    }

    /**
     * get user posts
     */
    public function posts(){
        try {
            $user = Auth::user();
            $posts = $user->posts;
            
            return $this->successResponse('successfully fetched user posts', $posts, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch user posts', $this->formatException($e), 500);
        }
    }

    /**
     * get user liked posts
     */
    public function likedPosts(){
        try {
            $posts = Auth::user()->likedPosts;
            
            return $this->successResponse('successfully fetched user liked posts', $posts, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch user liked posts', $this->formatException($e), 500);
        }
    }

    /**
     * change password
     */
    public function changePassword(ChangePasswordRequest $request){
        try {
            $request->validated();

            $user = Auth::user();

            $isPasswordCorrect = Hash::check($request->old_password, $user->password);
            if(!$isPasswordCorrect){
                return $this->errorResponse('old password is incorrect', null, 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->successResponse('successfully changed password', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to change password', $this->formatException($e), 500);
        }
    }

    /**
     * update profile
     */
    public function updateProfile(Request $request){
        try {
            $user = Auth::user();

            $user->fill($request->only([
                'first_name',
                'last_name',
                'profile_image',
            ]));
            $user->save();

            return $this->successResponse('successfully updated profile', $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to update profile', $this->formatException($e), 500);
        }
    }
}
