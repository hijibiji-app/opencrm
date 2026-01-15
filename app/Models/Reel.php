<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'content',
        'caption',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
