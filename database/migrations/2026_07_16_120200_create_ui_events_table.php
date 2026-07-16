<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name', 100)->index();
            $table->string('page', 255)->nullable();
            $table->string('target', 120)->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('entity');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_events');
    }
};
