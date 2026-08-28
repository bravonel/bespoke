<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;

class ActivityEventTriggerManager
{
    public function dropUpdateProtection(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_update ON activity_events'),
            'sqlite', 'mysql', 'mariadb' => DB::unprepared('DROP TRIGGER IF EXISTS activity_events_no_update'),
            default => null,
        };
    }

    public function restoreUpdateProtection(): void
    {
        $this->dropUpdateProtection();

        match (DB::getDriverName()) {
            'sqlite' => DB::unprepared("CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events BEGIN SELECT RAISE(ABORT, 'activity_events are append-only'); END;"),
            'mysql', 'mariadb' => DB::unprepared("CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_events are append-only'"),
            'pgsql' => DB::unprepared('CREATE TRIGGER activity_events_no_update BEFORE UPDATE ON activity_events FOR EACH ROW EXECUTE FUNCTION reject_activity_event_mutation()'),
            default => null,
        };
    }
}
