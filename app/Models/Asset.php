<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'asset_type',
        'currency',
    ];

    public function holdings(): HasMany
    {
        return $this->hasMany(Holding::class);
    }
}
