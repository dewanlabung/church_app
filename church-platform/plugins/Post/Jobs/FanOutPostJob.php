<?php

namespace Plugins\Post\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\Post;

class FanOutPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        $post = $this->post;

        // Skip private posts
        if ($post->privacy === 'only_me') {
            return;
        }

        // Get followers who should see this post
        $followerIds = DB::table('social_follows')
            ->where('following_id', $post->user_id)
            ->pluck('follower_id');

        // Always include the author
        $recipientIds = $followerIds->merge([$post->user_id])->unique();

        // If community post, include community members
        if ($post->community_id) {
            $memberIds = DB::table('community_members')
                ->where('community_id', $post->community_id)
                ->where('status', 'active')
                ->pluck('user_id');
            $recipientIds = $recipientIds->merge($memberIds)->unique();
        }

        // Batch insert into feeds table
        $rows = $recipientIds->map(fn($uid) => [
            'user_id'    => $uid,
            'post_id'    => $post->id,
            'created_at' => now(),
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('social_feeds')->insertOrIgnore($chunk);
        }
    }
}
