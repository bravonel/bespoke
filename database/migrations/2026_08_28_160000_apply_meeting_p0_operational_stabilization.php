<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workloads', function (Blueprint $table) {
            $table->unsignedSmallInteger('personal_priority')->nullable()->after('estimated_minutes');
            $table->index(['task_id', 'user_id'], 'project_workloads_task_user_index');
            $table->dropForeign(['task_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });

        DB::table('tasks')
            ->where('status', 'blocked')
            ->update(['status' => 'todo']);

        DB::table('projects')->where('current_stage', 'brief')->update(['current_stage' => 'initial']);
        DB::table('projects')->where('current_stage', 'medical_review')->update(['current_stage' => 'medical']);
        DB::table('projects')->where('current_stage', 'client_review')->update(['current_stage' => 'client']);
        DB::table('projects')->where('current_stage', 'ready_to_submit')->update(['current_stage' => 'client']);
        DB::table('users')->where('area', 'Redacción')->update(['area' => 'Copy']);

        DB::table('tasks')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->each(function (object $task): void {
                $alreadyAssigned = DB::table('project_workloads')
                    ->where('task_id', $task->id)
                    ->where('user_id', $task->assigned_to)
                    ->exists();

                if ($alreadyAssigned) {
                    return;
                }

                $user = DB::table('users')->where('id', $task->assigned_to)->first();
                $role = match ($user?->area) {
                    'Cuentas' => 'accounts',
                    'Medical', 'Médico' => 'medical',
                    'Copy', 'Redacción' => 'copy',
                    'Social Media', 'Redes sociales' => 'social_media',
                    'Cliente' => 'client',
                    default => 'design',
                };

                DB::table('project_workloads')->insert([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to,
                    'role' => $role,
                    'work_date' => $task->planned_for,
                    'estimated_minutes' => $task->estimated_minutes,
                    'personal_priority' => $task->personal_priority,
                    'notes' => $task->title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('projects')->where('current_stage', 'initial')->update(['current_stage' => 'brief']);
        DB::table('projects')->where('current_stage', 'medical')->update(['current_stage' => 'medical_review']);
        DB::table('projects')->where('current_stage', 'client')->update(['current_stage' => 'client_review']);
        DB::table('users')->where('area', 'Copy')->update(['area' => 'Redacción']);

        Schema::table('project_workloads', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->dropIndex('project_workloads_task_user_index');
            $table->dropColumn('personal_priority');
        });
    }
};
