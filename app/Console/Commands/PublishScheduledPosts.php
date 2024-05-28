<?php

namespace App\Console\Commands;

use App\Events\PostEvent;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Scheduled Posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $posts = Post::where('is_published', false)
                        ->where('publish_at', '<=', now())
                        ->get();

        foreach ($posts as $post) {
            $post->is_published = true;
            $post->save();

            $post->load([
                'user:id,first_name,last_name,email,profile_image',
                'likes.user:id,first_name,last_name,email,profile_image',
                'comments.user:id,first_name,last_name,email,profile_image'
            ]);

            PostEvent::dispatch($post);
        }

        return;
    }
}
