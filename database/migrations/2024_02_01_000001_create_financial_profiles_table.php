<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('monthly_income', 14, 2)->default(0);
            $table->decimal('monthly_expenses', 14, 2)->default(0);
            $table->decimal('savings', 14, 2)->default(0);
            $table->string('risk_tolerance')->default('moderate'); // ej. conservative / moderate / aggressive
            $table->unsignedInteger('investment_horizon_months')->default(36);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_profiles');
    }
};
