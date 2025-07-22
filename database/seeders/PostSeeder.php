<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            Post::factory()->create([
                'user_id' => $user->id
            ]);
        }
    }
}
