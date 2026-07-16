<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => $this->sqliteUp(),
            'mysql', 'mariadb' => $this->mysqlUp(),
            'pgsql' => $this->postgresUp(),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'sqlite', 'mysql', 'mariadb' => $this->dropSimpleTriggers(),
            'pgsql' => $this->postgresDown(),
            default => null,
        };
    }

    private function dropSimpleTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_delete');
    }

    private function postgresDown(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_update ON activity_events');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_delete ON activity_events');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_activity_event_mutation()');
    }

    private function sqliteUp(): void
    {
        DB::unprepared("CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events BEGIN SELECT RAISE(ABORT, 'activity_events are append-only'); END;");
        DB::unprepared("CREATE TRIGGER activity_events_no_delete BEFORE DELETE ON activity_events BEGIN SELECT RAISE(ABORT, 'activity_events are append-only'); END;");
    }

    private function mysqlUp(): void
    {
        DB::unprepared("CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_events are append-only'");
        DB::unprepared("CREATE TRIGGER activity_events_no_delete BEFORE DELETE ON activity_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_events are append-only'");
    }

    private function postgresUp(): void
    {
        DB::unprepared("CREATE FUNCTION reject_activity_event_mutation() RETURNS trigger AS \\$\\$ BEGIN RAISE EXCEPTION 'activity_events are append-only'; END; \\$\\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events FOR EACH ROW EXECUTE FUNCTION reject_activity_event_mutation()');
        DB::unprepared('CREATE TRIGGER activity_events_no_delete BEFORE DELETE ON activity_events FOR EACH ROW EXECUTE FUNCTION reject_activity_event_mutation()');
    }
};
