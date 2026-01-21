<?php
namespace Modules\Auth\Services;

use App\Enums\AuditAction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Password;
use App\Enums\KycStatus;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Events\SignupSucessfullEvent;
use Modules\Circle\Repositories\Contracts\CircleInviteRepositoryInterface;
use Modules\Core\Events\AuditLogged;
use Modules\Core\Repositories\Contracts\UserMetaRepositoryInterface;
use Modules\Referral\Services\ReferralService;

class AuthService
{
    use ResponseTrait;

    public function __construct(protected AuthRepositoryInterface $authRepo,
    protected UserMetaRepositoryInterface $metaRepo,
    protected ReferralService $referralService,
    protected CircleInviteRepositoryInterface $inviteRepo)
    {}

    /**
     * Handle user signup
     */
    public function signup(array $data)
    {
        DB::beginTransaction();
        try {
            $data['password'] = Hash::make($data['password']);
            $user = $this->authRepo->create($data);
            //assign role
            $user->assignRole('user');

            //create wallet
            $user->wallet()->create();
            $this->metaRepo->create([
                'user_id' => $user->id
            ]);

            $referralData = null;
            if(!empty($data['referral_code']))
            {
                $referralData = $this->referralService->logReferralByCode(
                    $data['referral_code'],
                    $user->id,
                    'user'
                );
            }
            $this->inviteRepo->linkInviteToUser($user);
            event(new SignupSucessfullEvent($user)); //for posthog
            event(new AuditLogged(
                userId: $user->id,
                action: AuditAction::NDPR_CONSENT_GIVEN->value,
                entityType: 'User',
                entityId: $user->id,
                metadata: [
                    'consent' => true,
                    'source' => 'signup'
                ],
                version: null
            ));
            //send email verification.
            $user->sendEmailVerificationNotification();
             DB::commit();

            return $this->success_response([
                'user' => $user,
                'referral_info' => $referralData, // Return referral info
            ], 'Email Verification Sent',201);
        } catch (Exception $e) {
             DB::rollBack();
              Log::info('signup ',[
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 400
            ]);
             $this->reportError($e,"Auth",[
                'action' => 'signup',
             ]);
             return $this->error_response($e->getMessage(),$e->getCode() ?: 400);
        }
    }

    /**
     * Handle login
     */
    public function login(array $credentials)
    {

        try {
            if (!Auth::attempt($credentials)) {
                 return $this->error_response('Invalid credentials', 401);
            }

            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;
            $this->inviteRepo->inAppNotifyPendingInvites($user);
            $nextStep = 'none';

            if (! $user->hasVerifiedEmail()) {
                return $this->error_response('Email not verified', 403);
            } elseif ($user->kyc_status !== KycStatus::VERIFIED) {
                $nextStep = $user->kyc_status->nextStep();
            }
            $data = [
                'token' => $token,
                'next_step' => $nextStep,
                'user' => $user->load('wallet:balance')
             ];
            return $this->success_response($data, 'Login successful',201);
        } catch (Exception $e) {
            $this->reportError($e,"Auth",[
                 'action' => 'login',
                 'service' => 'authService'
            ]);
            return $this->error_response($e->getMessage(),$e->getCode() ?: 400);
        }
    }

    /**
     * Handle forgot password
     */
    public function forgotPassword(array $data )
    {
        try {
            $status = Password::sendResetLink($data);

            if ($status !== Password::RESET_LINK_SENT) {
                return $this->error_response(__($status), 400);
            }

            return $this->success_response([], 'Password reset link sent to your email', 200);

        } catch (Exception $e) {
            $this->reportError($e,"Auth",[
                'action' => 'forgotPassword',
                'service' => 'authService'
            ]);
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function resetPassword(array $data)
    {
        try {
            $status = Password::reset(
                $data,
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                return $this->error_response(__($status), 400);
            }

            return $this->success_response([], 'Password reset successful', 200);

        } catch (Exception $e) {
             $this->reportError($e,"Auth",[
                'action' => 'verifyEmail',
            ]);
            $this->reportError($e,"Auth",[
                'action' => 'resetPassword',
                'service' => 'authService'
            ]);
            return $this->error_response($e->getMessage(),$e->getCode() ?: 400);
        }
    }


     public function verifyEmail( $id, $hash)
    {
        try{
             $user = $this->authRepo->find($id);

            if (!$user) {
                return $this->error_response('User not found', 404);
            }

            if (!hash_equals((string)$hash, sha1($user->getEmailForVerification()))) {
                return $this->error_response('Invalid verification link', 403);
            }

            if ($user->hasVerifiedEmail()) {
                return $this->success_response([], 'Email already verified');
            }

            $user->markEmailAsVerified();

            return $this->success_response([], 'Email verified successfully');
        }catch(Exception $e){
            $this->reportError($e,"Auth",[
                'action' => 'verifyEmail',
                'service' => 'authService'
            ]);
            return $this->error_response($e->getMessage(),$e->getCode() ?: 400);
        }
    }
    /**
     * Handle logout
     */
    public function logout($user)
    {
        try {
            $user->currentAccessToken()->delete();
            return $this->success_response([], 'Logged out successfully',200);
        } catch (Exception $e) {
            $this->reportError($e,"Auth",[
                'action' => 'logout',
                'service' => 'authService'
            ]);
           return $this->error_response($e->getMessage(),$e->getCode() ?: 400);
        }
    }
}
