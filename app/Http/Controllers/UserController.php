<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * get logged in user details
     */
    public function me(){
        try {
            $user = Auth::user();

            return $this->successResponse('successfully fetche user details', $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetche user details', $e, 500); 
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
            return $this->errorResponse('failed to fetch user posts', $e, 500);
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
            return $this->errorResponse('failed to change password', $e, 500);
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
            return $this->errorResponse('failed to update profile', $e, 500);
        }
    }
}
