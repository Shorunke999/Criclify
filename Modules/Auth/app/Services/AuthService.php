<?php
namespace Modules\Auth\Services;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Password;
use App\Enums\KycStatus;
use App\Models\User;
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

            $user = $this->createUserWithRole(
                userData: $data,
                role: 'user',
                meta: [],
                status: AccountStatus::APPROVED
            );

            // user-only concerns
            $user->wallet()->create();

            $referralData = null;
            if (!empty($data['referral_code'])) {
                $referralData = $this->referralService->logReferralByCode(
                    $data['referral_code'],
                    $user->id,
                    'user'
                );
            }

            $this->inviteRepo->linkInviteToUser($user);

            event(new SignupSucessfullEvent($user));
            $user->sendEmailVerificationNotification();

            DB::commit();

            return $this->success_response(
                ['user' => $user, 'referral_info' => $referralData],
                'Email Verification Sent',
                201
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Handle Creator Invite
     */
    public function signupCreator(array $data)
    {
        DB::beginTransaction();

        try {
            $existingUser = $this->authRepo->findByEmail($data['email']);
            if ($existingUser) {
                if (! $existingUser->hasRole('creator')) {
                    return $this->error_response(
                        'An account with this email already exists',
                        409
                    );
                }

                return match ($existingUser->account_status) {
                    AccountStatus::APPROVED =>
                        $this->error_response(
                            'Your creator account is already approved. Please login.',
                            409
                        ),

                    AccountStatus::PENDING =>
                        $this->success_response(
                            [],
                            'Your creator account is currently under review'
                        ),

                    AccountStatus::DENIED =>
                        $this->reopenCreatorAccount($existingUser, $data),
                };
            }

            $meta = [
                'country_id' => $data['country_id'],
                'occupation' => $data['occupation'],
                'phone_number' => $data['phone_number'],
            ];
            unset($data['occupation'], $data['phone_number'],$data['country_id']);
            $this->createUserWithRole(
                userData: $data,
                role: 'creator',
                meta: $meta,
                status: AccountStatus::PENDING
            );

            DB::commit();
            return $this->success_response(
                [],
                'Invite successful. Please wait for admin approval',
                201
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Handle login
     */
    public function login(array $credentials)
    {

        try {
            $accountIsActive = User::where('email',$credentials['email'])
                ->where('account_status', AccountStatus::APPROVED)
                ->exists();
            if(!$accountIsActive) return $this->error_response('Account is not active',401);
            if (!Auth::attempt($credentials)) {
                 return $this->error_response('Invalid credentials', 401);
            }

            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;
            if($user->role('admin')) return  $this->success_response($token, 'Login successful',201);
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

    protected function reopenCreatorAccount(User $user, array $data)
    {
        // Update editable fields only
        $user->update([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'account_status' => AccountStatus::PENDING,
        ]);

        DB::commit();

        return $this->success_response(
            [],
            'Your creator application has been resubmitted for review'
        );
    }
    protected function createUserWithRole(
    array $userData,
    string $role,
    array $meta = [],
    AccountStatus $status
    ): User {
        $userData['account_status'] = $status;
        $user = $this->authRepo->create($userData);

        $user->assignRole($role);

        $this->metaRepo->create([
            'user_id' => $user->id,
            ...$meta,
        ]);

        event(new AuditLogged(
            userId: $user->id,
            action: AuditAction::NDPR_CONSENT_GIVEN->value,
            entityType: 'User',
            entityId: $user->id,
            metadata: [
                'consent' => true,
                'source' => "signup_{$role}"
            ]
        ));

        return $user;
    }
}
