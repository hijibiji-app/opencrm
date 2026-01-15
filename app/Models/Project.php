<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'category',
        'domain',
        'status',
        'user_id',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created the project.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
