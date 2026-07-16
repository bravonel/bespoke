<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_assistant_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction', 12)->index();
            $table->string('from_phone', 24)->nullable()->index();
            $table->string('to_phone', 24)->nullable();
            $table->string('message_type', 30)->default('text');
            $table->text('body')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
