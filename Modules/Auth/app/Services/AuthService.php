<?php
namespace Modules\Auth\Services;

use App\Enums\AccountStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Exception;
use App\Enums\KycStatus;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Events\SignupSucessfullEvent;
use Modules\Circle\Repositories\Contracts\CircleInviteRepositoryInterface;
use Modules\Cooperative\Enums\CooperativeStatusEnum;
use Modules\Cooperative\Repositories\Contracts\CooperativeRepositoryInterface;
use Modules\Core\Repositories\Contracts\UserMetaRepositoryInterface;
use Modules\Referral\Services\ReferralService;

class AuthService
{
    use ResponseTrait;

    public function __construct(protected AuthRepositoryInterface $authRepo,
    protected UserMetaRepositoryInterface $metaRepo,
    protected ReferralService $referralService,
    protected CircleInviteRepositoryInterface $inviteRepo,
    protected OtpService $otpService,
    protected CooperativeRepositoryInterface $cooperativeRepo)
    {}

    /**
     * Handle user signup
     */
    public function signup(array $data)
    {
        DB::beginTransaction();

        try {
            $data['password'] = Hash::make($data['password']);
            if ($this->authRepo->findByEmail($data['email'])) {
                return $this->error_response(
                    'An account with this email already exists',
                    409
                );
            }
            $user = $this->createUser(
                userData: $data,
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
            // Generate and send OTP
            $this->otpService->generate($user, 'email_verification');
            DB::commit();

            event(new SignupSucessfullEvent($user));

            return $this->success_response(
                [
                    'user' => $user->only(['id', 'email', 'first_name', 'last_name']),
                    'referral_info' => $referralData,
                    'message' => 'OTP sent to your email. Please verify to continue.'
                ],
                'Signup successful. Please check your email for OTP.',
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
    public function signupRole(array $data)
    {
        DB::beginTransaction();

        try {
            $existingUser = $this->authRepo->findByEmail($data['email']);
            if ($existingUser) {
                if (! $existingUser->hasRole($data['role'])) {
                    return $this->error_response(
                        'An account with this email already exists',
                        409
                    );
                }

                return match ($existingUser->account_status){
                    AccountStatus::APPROVED =>
                        $this->error_response(
                            'Your account is already approved. Please login.',
                            409
                        ),

                    AccountStatus::PENDING =>
                        $this->success_response(
                            [],
                            'Your account is currently under review'
                        ),

                    AccountStatus::DENIED =>
                        $this->reopenCreatorAccount($existingUser, $data),
                };
            }
            $this->createUserWithRole(
                data: $data,
                role: $data['role'],
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
            if (!Auth::attempt($credentials)) {
                 return $this->error_response('Invalid credentials', 401);
            }
             $user = Auth::user();
            if($user->account_status !== AccountStatus::APPROVED) return $this->error_response('Account is not active',401);

            $token = $user->createToken('api-token')->plainTextToken;
            if($user->role('admin')) return  $this->success_response($token, 'Login successful',201);
            $this->inviteRepo->inAppNotifyPendingInvites($user);
            $nextStep = 'none';

            if (! $user->hasVerifiedEmail()) {
                $this->otpService->generate($user, 'email_verification');
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
     * Verify email with OTP
     */
    public function verifyEmailWithOtp(array $data)
    {
        try {
            $user = $this->authRepo->findByEmail($data['email']);

            if (!$user) {
                return $this->error_response('User not found', 404);
            }

            if ($user->hasVerifiedEmail()) {
                return $this->success_response([], 'Email already verified');
            }

            // Verify OTP
            $this->otpService->verify($user, $data['otp'], 'email_verification');

            // Mark email as verified
            $user->markEmailAsVerified();

            // Generate auth token for mobile
            $token = $user->createToken('api-token')->plainTextToken;

            return $this->success_response([
                'token' => $token,
                'user' => $user->load('wallet'),
                'next_step' => $user->kyc_status !== KycStatus::VERIFIED
                    ? $user->kyc_status->nextStep()
                    : 'none'
            ], 'Email verified successfully', 200);

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(array $data)
    {
        try {
            $user = $this->authRepo->findByEmail($data['email']);

            if (!$user) {
                return $this->error_response('User not found', 404);
            }

            if ($user->hasVerifiedEmail()) {
                return $this->error_response('Email already verified', 400);
            }

            $this->otpService->resend($user, 'email_verification');

            return $this->success_response(
                [],
                'OTP resent successfully. Please check your email.',
                200
            );

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    /**
     * Request password reset OTP
     */
    public function forgotPassword(array $data)
    {
        try {
            $user = $this->authRepo->findByEmail($data['email']);

            if (!$user) {
                // Don't reveal if email exists
                return $this->success_response(
                    [],
                    'If an account exists with this email, an OTP has been sent.',
                    200
                );
            }

            $this->otpService->generate($user, 'password_reset');

            return $this->success_response(
                [],
                'Password reset OTP sent to your email',
                200
            );

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Reset password with OTP
     */
    public function resetPasswordWithOtp(array $data)
    {
        try {
            $user = $this->authRepo->findByEmail($data['email']);

            if (!$user) {
                return $this->error_response('User not found', 404);
            }

            // Verify OTP
            $this->otpService->verify($user, $data['otp'], 'password_reset');

            // Reset password
            $user->update([
                'password' => Hash::make($data['password'])
            ]);

            // Revoke all tokens
            $user->tokens()->delete();

            return $this->success_response(
                [],
                'Password reset successful. Please login with your new password.',
                200
            );

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
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
    array $data,
    string $role,
    AccountStatus $status
    ){
        $userData = Arr::only($data, [
            'first_name',
            'last_name',
            'email',
        ]);
        $meta = Arr::only($data, [
            'country_id',
            'occupation',
            'phone_number',
            'role_in_org',
            'experience',
            'type_of_group',
            'group_duration',
            'can_enforce_rules_off_app',
        ]);
        $userData['account_status'] = $status;
        $user = $this->authRepo->create($userData);

        $user->assignRole($role);

        $this->metaRepo->create([
            'user_id' => $user->id,
            ...$meta,
        ]);
        if($role === 'cooperative')
        {
            $cooperativeData = Arr::only($data, [
                'country_id',
                'organisation_name',
                'organisation_type',
                'organisation_reg_number',
                'organisation_established_year',
                'approx_member_number',
                'has_existing_scheme',
                'current_contribution_management',
                'governance_structure',
                'intended_api_usage',
                'organisation_handles_payments',
                'has_internal_default_rules',
            ]);
             $this->cooperativeRepo->create([
                'owner_id'    => $user->id,
                'status' => CooperativeStatusEnum::Pending,
                ...$cooperativeData,
             ]);
             
        }
        $user->inviteCompliance()->create([
            'creator_context' => $data['role'] === 'creator'
                ? Arr::only($data, [
                    'collection_methods',
                    'number_of_members',
                    'expected_monthly_contribution',
                    'contribution_frequency',
                    'missed_contribution_handling',
                ])
                : null,

            'organisation_context' => $data['role'] === 'cooperative'
                ? Arr::only($data, [
                    'estimated_circle_count',
                    'intended_api_usage',
                ])
                : null,

            'not_a_bank_acknowledged' => true,
            'no_fund_safeguard_acknowledged' => true,
            'fixed_payout_acknowledged' => true,
            'agree_to_terms' => true,

            'additional_context' => $data['additional_context'] ?? null,
         ]);
        return;
    }

    protected function createUser( array $userData,array $meta = [],
     AccountStatus $status ): User 
    { 
        $userData['account_status'] = $status;
        $user = $this->authRepo->create($userData);
        $user->assignRole('user');
        $this->metaRepo->create([ 'user_id' => $user->id, ...$meta, ]);
        return $user;
     }
}
