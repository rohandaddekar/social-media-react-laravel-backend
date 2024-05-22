<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Mail\ForgotPasswordEmail;
use App\Mail\VerifyEmail;
use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponse;

    public function signUp(SignUpRequest $request){
        try {
            $request->validated();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken($user->email)->plainTextToken;

            $emailVerificationToken = mt_rand(100000, 999999);

            EmailVerificationToken::create([
                'email' => $user->email,
                'token' => $emailVerificationToken
            ]);

            Mail::to($user->email)->send(new VerifyEmail($emailVerificationToken));

            $data = [
                'user' => $user,
                'token' => $token,
            ];

            return $this->successResponse('successfully signed up', $data, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to sign up', $e, 500);
        }
    }

    public function signIn(SignInRequest $request){
        try {
            $request->validated();

            if(!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'success' => false,
                    'message' => 'invalid credentials'
                ], 401);
            }

            $user = Auth::user();

            $token = $user->createToken($user->email)->plainTextToken;

            $data = [
                'user' => $user,
                'token' => $token
            ];

            return $this->successResponse('successfully signed in', $data, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to sign in', $e, 500);
        }
    }

    public function signOut(){
        try {
            Auth::user()->tokens()->delete();

            return $this->successResponse('successfully signed out', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to sign out', $e, 500);
        }
    }

    public function forgotPassword(Request $request) {
        try {
            $request->validate([
                'email' => ['required', 'email'],
            ]);

            $user = User::where('email', $request->email)->first();
            if(!$user){
                return $this->errorResponse('user not found', null, 404);
            }

            $forgotPasswordToken = Str::random(64);
            PasswordResetToken::updateOrCreate(
                ['email' => $user->email],
                ['token' => $forgotPasswordToken]
            );

            Mail::to($user->email)->send(new ForgotPasswordEmail($forgotPasswordToken));

            return $this->successResponse('forgot password email sent successfully', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to send reset password email', $e, 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request) {
        try {
            $request->validated();

            $user = User::where('email', $request->email)->first();
            if(!$user){
                return $this->errorResponse('user not found', null, 404);
            }

            $resetPasswordToken = PasswordResetToken::where('email', $request->email)
                                        ->where('token', $request->token)
                                        ->first();
            if(!$resetPasswordToken){
                return $this->errorResponse('invalid token', null, 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();
            $resetPasswordToken->delete();

            return $this->successResponse('password reset successfully', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to reset password', $e, 500);
        }
    }
    
    public function verifyEmail(VerifyEmailRequest $request) {
        try {
            $request->validated();

            $user = User::where('email', $request->email)->first();
            if(!$user){
                return $this->errorResponse('user not found', null, 404);
            }

            if($user->email_verified_at){
                return $this->errorResponse('email already verified', null, 404);
            }

            $userEmailToken = EmailVerificationToken::where('email', $request->email)
                                        ->where('token', $request->token)
                                        ->first();
            if(!$userEmailToken){
                return $this->errorResponse('invalid token', null, 404);
            }

            $user->email_verified_at = now();
            $user->save();
            $userEmailToken->delete();

            return $this->successResponse('email verified successfully', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to send verification email', $e, 500);
        }
    }
    
    public function verifyEmailResend(Request $request) {
        try {
            $request->validate([
                'email' => ['required', 'email'],
            ]);

            $user = User::where('email', $request->email)->first();
            if(!$user){
                return $this->errorResponse('user not found', null, 404);
            }

            if($user->email_verified_at){
                return $this->errorResponse('email already verified', null, 404);
            }

            $emailVerificationToken = mt_rand(100000, 999999);
            EmailVerificationToken::updateOrCreate(
                ['email' => $user->email],
                ['token' => $emailVerificationToken]
            );

            Mail::to($user->email)->send(new VerifyEmail($emailVerificationToken));

            return $this->successResponse('verification email sent successfully', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to resend verification email', $e, 500);
        }
    }
}

