<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFollow;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserFollowController extends Controller
{
    use ApiResponse;

    public function follow(Request $request, $userId){
        try {
            $user = User::find($userId);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $alreadySent = UserFollow::where('follower_id', Auth::user()->id)->where('followed_id', $user->id)->first();
            if($alreadySent) return $this->errorResponse('already sent follow request', null, 409);

            $follow = UserFollow::create([
                'follower_id' => Auth::user()->id,
                'followed_id' => $user->id,
                'status' => 'pending'
            ]);

            return $this->successResponse('successfully send follow request', $follow, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to follow', $this->formatException($e), 500); 
        }
    }

    public function unFollow(Request $request, $userId){
        try {
            $user = User::find($userId);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $isFollowing = UserFollow::where('follower_id', Auth::user()->id)->where('followed_id', $user->id)->first();
            if(!$isFollowing) return $this->errorResponse('you are not following this user', null, 404);

            $isFollowing->delete();

            return $this->successResponse('successfully unfollowed', $isFollowing, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to unfollow', $this->formatException($e), 500); 
        }
    }

    public function acceptFollow(Request $request, $id){
        try {
            $follow = UserFollow::find($id);
            if(!$follow) return $this->errorResponse('follow request not found', null, 404);

            if($follow->followed_id !== Auth::user()->id) return $this->errorResponse('unauthorized', null, 401);

            $follow->status = 'accepted';
            $follow->save();

            return $this->successResponse('successfully accepted follow request', $follow, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to accept follow request', $this->formatException($e), 500); 
        }
    }

    public function followers($userId){
        try {
            $user = User::find($userId);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $followers = $user->followers()->get(); 

            return $this->successResponse('successfully fetched followers', $followers, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch followers', $this->formatException($e), 500); 
        }
    }

    public function followings($userId){
        try {
            $user = User::find($userId);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $followings = $user->following()->get(); 

            return $this->successResponse('successfully fetched followings', $followings, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch followings', $this->formatException($e), 500); 
        }
    }
}
