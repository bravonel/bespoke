<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workloads', function (Blueprint $table) {
            $table->foreignId('task_id')
                ->nullable()
                ->after('project_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_workloads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
        });
    }
};
