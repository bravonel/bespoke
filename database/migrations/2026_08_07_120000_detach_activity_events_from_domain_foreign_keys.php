<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            // Audit events must outlive domain records without being mutated by nullOnDelete.
            $table->dropForeign(['actor_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['client_id']);
        });

        $this->restoreSqliteAuditTriggers();
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });

        $this->restoreSqliteAuditTriggers();
    }

    private function restoreSqliteAuditTriggers(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_delete');
        DB::unprepared("CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events BEGIN SELECT RAISE(ABORT, 'activity_events are append-only'); END;");
        DB::unprepared("CREATE TRIGGER activity_events_no_delete BEFORE DELETE ON activity_events BEGIN SELECT RAISE(ABORT, 'activity_events are append-only'); END;");
    }
};
