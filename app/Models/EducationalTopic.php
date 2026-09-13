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

    /**
     * Etiqueta e ícono por categoría, para Topic Row / Topic Detail Panel
     * (Figma nodo 40:182 / 42:198). `category` es un string libre igual que
     * `risk_tolerance` en FinancialProfile -- un valor sin mapear cae al
     * genérico en vez de romper la vista.
     */
    private const CATEGORIES = [
        'personal_finance' => ['label' => 'Finanzas personales', 'icon' => 'dollar-sign'],
        'basics' => ['label' => 'Fundamentos', 'icon' => 'trending-up'],
        'investments' => ['label' => 'Inversiones', 'icon' => 'briefcase'],
        'risk' => ['label' => 'Riesgo', 'icon' => 'pie-chart'],
    ];

    private const DIFFICULTIES = [
        'beginner' => 'Principiante',
        'intermediate' => 'Intermedio',
        'advanced' => 'Avanzado',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function categoryIcon(): string
    {
        return self::CATEGORIES[$this->category]['icon'] ?? 'dollar-sign';
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTIES[$this->difficulty] ?? ucfirst($this->difficulty);
    }
}
