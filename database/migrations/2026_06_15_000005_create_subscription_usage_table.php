<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->integer('used')->default(0);
            $table->timestamp('period_start');
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'feature_id', 'period_start'], 'usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};
