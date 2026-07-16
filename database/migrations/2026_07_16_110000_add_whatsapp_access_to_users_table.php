<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_phone', 24)->nullable()->unique()->after('email');
            $table->boolean('whatsapp_enabled')->default(false)->index()->after('whatsapp_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_phone']);
            $table->dropColumn(['whatsapp_phone', 'whatsapp_enabled']);
        });
    }
};
