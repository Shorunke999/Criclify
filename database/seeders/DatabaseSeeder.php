<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();
        foreach($users as $user)
        {
            $posts = $user->posts()->createMany(PostFactory::count(5)->make()->toArray());
            foreach($posts as $post)
            {
                $post->searchable();
            }
        }
    }
}
