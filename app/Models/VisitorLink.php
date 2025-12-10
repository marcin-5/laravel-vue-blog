<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLink extends Model
{
    protected $fillable = [
        'visitor_id',
        'user_id',
    ];
}
