<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->unsignedSmallInteger('personal_priority')->nullable()->after('priority');
            $table->text('blocked_reason')->nullable()->after('description');
            $table->text('return_reason')->nullable()->after('blocked_reason');
            $table->timestamp('started_at')->nullable()->after('due_at');
            $table->timestamp('delivered_at')->nullable()->after('started_at');
            $table->timestamp('finalized_at')->nullable()->after('delivered_at');

            $table->index(['assigned_to', 'planned_for', 'personal_priority'], 'tasks_personal_plan_priority_index');
        });

        DB::table('tasks')
            ->where('status', 'done')
            ->update([
                'delivered_at' => DB::raw('completed_at'),
                'completed_at' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('tasks')
            ->whereIn('status', ['done', 'finalized'])
            ->update([
                'status' => 'done',
                'completed_at' => DB::raw('COALESCE(finalized_at, delivered_at, updated_at)'),
            ]);

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_personal_plan_priority_index');
            $table->dropColumn([
                'personal_priority',
                'blocked_reason',
                'return_reason',
                'started_at',
                'delivered_at',
                'finalized_at',
            ]);
        });
    }
};
