<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::all();

        if ($posts->isEmpty()) {
            return;
        }

        // Half of the posts
        $postsToExtend = $posts->random($posts->count() / 2);

        $postsToExtend->each(function (Post $post) use ($posts) {
            // 0 to 4 extensions
            $extensionCount = random_int(0, 4);

            if ($extensionCount === 0) {
                return;
            }

            // Pick random posts to be extensions (excluding the current post)
            $extensions = $posts->reject(fn($p) => $p->id === $post->id)
                ->random(min($extensionCount, $posts->count() - 1));

            foreach ($extensions as $index => $extension) {
                // Update visibility to 'extension' as requested/implied by logic
                $extension->update(['visibility' => Post::VIS_EXTENSION]);

                $post->extensions()->attach($extension->id, [
                    'display_order' => $index,
                ]);
            }
        });
    }
}
