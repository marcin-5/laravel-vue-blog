<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexNowQueuedUrl extends Model
{
    protected $table = 'index_now_queued_urls';

    protected $fillable = ['url', 'blog_id', 'post_id'];
}
