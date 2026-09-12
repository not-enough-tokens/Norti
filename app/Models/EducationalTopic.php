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
}
