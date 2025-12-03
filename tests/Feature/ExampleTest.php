<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_if_search_post_with_meilisearch()
    {

        $this->assertTrue($this->check_meilisearch_is_active(),"Meilisearch is not running");
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'PHP old',
            'content' => "we lift by Laravel others"
        ]);
        $post->searchable();

        $results = Post::search('PHP')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($post->id, $results->first()->id);


    }

    private function check_meilisearch_is_active():bool
    {
        $response = $this->get(config('scout.meilisearch.host').'/health');
        return $response != false;
    }
}
