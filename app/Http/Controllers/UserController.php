<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse, FileUpload;

    /**
     * get list of all users except logged in user
     */
    public function index(Request $request){
        try {
            $authUser = Auth::user();
            $query = User::where('id', '!=', $authUser->id)
                            ->whereNotNull('email_verified_at')
                            ->where('is_active', true);

            if ($request->query('showSuggested')) {
                $followingIds = $authUser->sentFollowRequests()->where('status', 'accepted')->pluck('receiver_id')->toArray();
                $followerIds = $authUser->receivedFollowRequests()->where('status', 'accepted')->pluck('sender_id')->toArray();

                $excludedIds = array_merge($followingIds, $followerIds);
    
                $query->whereNotIn('id', $excludedIds);
                $users = $query->inRandomOrder()->limit(5)->get();
            }  

            if ($request->query('chatUsers')) {
                $query->where(function ($query) use ($authUser) {
                    $query->whereHas('sentFollowRequests', function ($query) use ($authUser) {
                        $query->where('receiver_id', $authUser->id)->where('status', 'accepted');
                    })->orWhereHas('receivedFollowRequests', function ($query) use ($authUser) {
                        $query->where('sender_id', $authUser->id)->where('status', 'accepted');
                    });
                });
    
                $users = $query->get();
            }

            $users = $query->get();

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

            $newProfileImage = null;
            $newProfileBannerImage = null;

            if($request->hasFile('profile_image')) {
                $uploadedImages = $this->fileUpload([$request->file('profile_image')]);
                $newProfileImage = $uploadedImages[0] ?? null;
            }

            if($request->hasFile('profile_banner_image')) {
                $uploadedImages = $this->fileUpload([$request->file('profile_banner_image')]);
                $newProfileBannerImage = $uploadedImages[0] ?? null;
            }

            $user->first_name = $request->filled('first_name') ? $request->first_name : $user->first_name;
            $user->last_name = $request->filled('last_name') ? $request->last_name : $user->last_name;
            $user->about_me = $request->filled('about_me') ? $request->about_me : $user->about_me;
            $user->profile_image = $newProfileImage ?: ($user->profile_image ?: "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png");
            $user->profile_banner_image = $newProfileBannerImage ?: ($user->profile_banner_image ?: "https://res.cloudinary.com/omaha-code/image/upload/ar_4:3,c_fill,dpr_1.0,e_art:quartz,g_auto,h_396,q_auto:best,t_Linkedin_official,w_1584/v1561576558/mountains-1412683_1280.png");

            $user->save();

            return $this->successResponse('successfully updated profile', $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to update profile', $this->formatException($e), 500);
        }
    }
}
