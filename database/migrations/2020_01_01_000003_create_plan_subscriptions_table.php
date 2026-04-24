<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plan_subscriptions', 'plan_subscriptions'), function (Blueprint $table) {
            $table->id();
            $table->morphs('subscriber');
            $table->foreignId('plan_id')
                ->constrained(config('subscriptions.tables.plans', 'plans'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscriber_type', 'subscriber_id', 'slug']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plan_subscriptions', 'plan_subscriptions'));
    }
};
