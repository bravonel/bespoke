<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clients', 'brands', 'projects'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('status_before_archive')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        foreach (['clients', 'brands', 'projects'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('status_before_archive');
            });
        }
    }
};
