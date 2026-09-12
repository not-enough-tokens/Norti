<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalTopic extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'category',
        'difficulty',
        'estimated_minutes',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('completed_at')
            ->withTimestamps();
    }
}
