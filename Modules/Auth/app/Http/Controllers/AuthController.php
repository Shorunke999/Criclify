<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
     * POST /api/auth/signup
     */
    public function signup(SignupRequest $request)
    {
        return $this->authService->signup($request->validated());
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request)
    {
        return $this->authService->login($request->validated());
    }

    /**
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(ForgetPassword $request)
    {
        return $this->authService->forgotPassword($request->validated());
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService->resetPassword($request->validated());
    }
    /**
     * POST /api/auth/logout
     * Requires Sanctum auth middleware
     */
    public function logout(Request $request)
    {
        $user = $request->user(); // authenticated user via Sanctum

        return $this->authService->logout($user);
    }

    /**
     * GET /api/auth/email/verify/{id}/{hash}
     * Verify email endpoint
     */
    public function verifyEmail($id, $hash)
    {
        return $this->authService->verifyEmail($id,$hash);
    }
}
