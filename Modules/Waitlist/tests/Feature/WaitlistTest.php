<?php

namespace Modules\Waitlist\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Waitlist\Models\WaitlistEntry;
use Modules\Waitlist\Models\WaitlistQuestion;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
     use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }
     private function actingAsAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);
        return $admin;
    }


    public function test_user_can_join_waitlist_without_survey()
    {
        $payload = [
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $response = $this->postJson('/api/waitlist', $payload);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully joined the waitlist',
            ]);

        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'jane@example.com',
        ]);
    }

     public function test_waitlist_email_must_be_unique()
    {
        $this->postJson('/api/waitlist', [
            'name' => 'Jane',
            'email' => 'dup@example.com',
        ]);

        $response = $this->postJson('/api/waitlist', [
            'name' => 'Jane Again',
            'email' => 'dup@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_required_survey_question_is_enforced()
    {
        WaitlistQuestion::create([
            'key' => 'use_case',
            'label' => 'How will you use the app?',
            'type' => 'text',
            'required' => true,
            'active' => true,
        ]);

        $response = $this->postJson('/api/waitlist', [
            'name' => 'Jane',
            'email' => 'survey@example.com',
            'survey' => [],
        ]);

        $response->assertStatus(422);
    }

     public function test_admin_can_create_survey_question()
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/waitlist/questions', [
            'key' => 'industry',
            'label' => 'Your industry?',
            'type' => 'text',
            'required' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('waitlist_questions', [
            'key' => 'industry',
        ]);
    }

      public function test_non_admin_cannot_create_survey_question()
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/waitlist/questions', [
            'key' => 'fail',
            'label' => 'Fail',
            'type' => 'text',
        ])->assertStatus(403);
    }

    public function test_admin_can_toggle_question()
    {
        $this->actingAsAdmin();

        $question = \Modules\Waitlist\Models\WaitlistQuestion::factory()->create([
            'active' => true,
        ]);

        $this->patchJson("/api/admin/waitlist/questions/{$question->id}/toggle")
            ->assertOk();

        $this->assertDatabaseHas('waitlist_questions', [
            'id' => $question->id,
            'active' => false,
        ]);
    }

     public function test_admin_can_export_waitlist()
    {
       $this->actingAsAdmin();

        WaitlistEntry::factory()->count(3)->create();

        $response = $this->get('/api/admin/waitlist/export');

        $response->assertOk();
        $this->assertStringContainsString(
            'Name,Email,Referral,Survey',
            $response->streamedContent()
        );
    }

    public function test_export_can_filter_by_referral_code()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        WaitlistEntry::factory()->create([
            'referral_code' => 'WL_ABC123',
        ]);

        $response = $this->get('/api/admin/waitlist/export?referral_code=WL_ABC123');

        $this->assertStringContainsString(
            'WL_ABC123',
            $response->streamedContent()
        );
    }

}
