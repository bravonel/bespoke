<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date')->index();
            $table->string('metric_type', 30)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->string('dimension_key', 150)->nullable()->index();
            $table->string('page', 255)->nullable();
            $table->json('dimensions')->nullable();
            $table->unsignedBigInteger('event_count')->default(0);
            $table->unsignedBigInteger('session_count')->default(0);
            $table->unsignedBigInteger('active_seconds')->default(0);
            $table->unsignedBigInteger('idle_seconds')->default(0);
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();

            $table->index(['metric_type', 'metric_date']);
            $table->index(['user_id', 'metric_date']);
            $table->index(['project_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_daily_metrics');
    }
};
