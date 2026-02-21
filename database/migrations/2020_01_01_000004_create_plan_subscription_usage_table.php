<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_subscription_usage', 'plan_subscription_usage'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained(config('subscriptions.tables.plan_subscriptions', 'plan_subscriptions'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('feature_id')
                ->constrained(config('subscriptions.tables.features', 'features'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedInteger('used');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscription_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plan_subscription_usage', 'plan_subscription_usage'));
    }
};
