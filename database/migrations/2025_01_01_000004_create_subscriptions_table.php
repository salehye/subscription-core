<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscriber_type');
            $table->string('subscriber_id');
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // primary, addon
            $table->foreignId('parent_subscription_id')->nullable()
                ->constrained('subscriptions')
                ->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('status'); // active, canceled, expired, suspended, paused
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscriber_type', 'subscriber_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
