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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

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