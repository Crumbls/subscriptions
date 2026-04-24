<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.plans', 'plans'), function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price')->default(0);
            $table->decimal('signup_fee')->default(0);
            $table->string('currency', 3);
            $table->unsignedSmallInteger('trial_period')->default(0);
            $table->string('trial_interval')->default('day');
            $table->unsignedSmallInteger('invoice_period')->default(0);
            $table->string('invoice_interval')->default('month');
            $table->unsignedSmallInteger('grace_period')->default(0);
            $table->string('grace_interval')->default('day');
            $table->unsignedSmallInteger('active_subscribers_limit')->nullable();
            $table->unsignedMediumInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.plans', 'plans'));
    }
};
