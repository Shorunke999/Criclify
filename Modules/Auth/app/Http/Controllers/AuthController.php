<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\CreatorInviteRequest;
use Modules\Auth\Http\Requests\ForgetPassword;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Http\Requests\SignupRequest;
use Modules\Auth\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Sign up a new user
     * @unauthenticated
     */
    public function signup(SignupRequest $request)
    {
        return $this->authService->signup($request->validated());
    }

    /**
     * Sign up with Invite for creator
     * @unauthenticated
     */
    public function creatorInvite(CreatorInviteRequest $request)
    {
        return $this->authService->signupCreator(
            $request->validated()
        );
    }
    /**
     * Login user
     * @unauthenticated
     */
    public function login(LoginRequest $request)
    {
        return $this->authService->login($request->validated());
    }

    /**
     * Forgot password
     * @unauthenticated
     */
    public function forgotPassword(ForgetPassword $request)
    {
        return $this->authService->forgotPassword($request->validated());
    }
    /**
     * Reset password
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService->resetPassword($request->validated());
    }
    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $user = $request->user(); // authenticated user via Sanctum

        return $this->authService->logout($user);
    }

    /**
     * Verify email endpoint
     */
    public function verifyEmail($id, $hash)
    {
        return $this->authService->verifyEmail($id,$hash);
    }
}
