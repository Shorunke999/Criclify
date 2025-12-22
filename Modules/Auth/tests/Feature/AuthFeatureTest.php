<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Modules\Core\Models\UserMeta;
use Tests\TestCase;
class AuthFeatureTest extends TestCase
{
     use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run module migrations
        $this->artisan('migrate',['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_user_can_signup()
    {
        Notification::fake();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ndpr_consent' => true,
        ];

        $response = $this->postJson('/api/auth/signup', $payload);

        $response
            ->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email Verification Sent',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        $user = User::whereEmail('john@example.com')->first();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->hasRole('user'));

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }
    public function test_user_can_signup_with_referral_code()
    {
        Notification::fake();

        // Referrer
        $referrer = User::factory()->create();
        $referrerMeta = UserMeta::factory()->create([
            'user_id' => $referrer->id,
            'referral_code' => 'CRIC_TEST123',
            'referral_count' => 0,
        ]);

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => 'CRIC_TEST123',
            'ndpr_consent' => true,
        ];

        $response = $this->postJson('/api/auth/signup', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
        ]);

        $this->assertDatabaseHas('user_metas', [
            'user_id' => $referrer->id,
            'referral_count' => 1,
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_wrong_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'failed',
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_forgot_password_sends_reset_link()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password reset link sent to your email',
            ]);
    }

    public function test_user_can_reset_password()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password reset successful',
            ]);

        $this->assertTrue(
            Hash::check('newpassword123', $user->fresh()->password)
        );
    }

    public function test_user_can_verify_email()
    {
        $user = User::factory()->unverified()->create();

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson(
            "/api/auth/email/verify/{$user->id}/{$hash}"
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully',
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ]);
    }

}
