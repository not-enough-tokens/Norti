<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'monthly_income',
        'monthly_expenses',
        'savings',
        'risk_tolerance',
        'investment_horizon_months',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'monthly_expenses' => 'decimal:2',
        'savings' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
