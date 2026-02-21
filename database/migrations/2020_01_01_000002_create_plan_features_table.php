<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.features', 'features'), function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('resettable_period')->default(0);
            $table->string('resettable_interval')->default('month');
            $table->unsignedMediumInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(config('subscriptions.tables.plan_features', 'plan_features'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')
                ->constrained(config('subscriptions.tables.plans', 'plans'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('feature_id')
                ->constrained(config('subscriptions.tables.features', 'features'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('value');
            $table->unsignedMediumInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plan_features', 'plan_features'));
        Schema::dropIfExists(config('subscriptions.tables.features', 'features'));
    }
};
