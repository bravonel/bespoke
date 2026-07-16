<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            // Deliberately not a foreign key: canonical events outlive session-detail retention.
            $table->unsignedBigInteger('user_session_id')->nullable()->index()->after('actor_id');
            $table->string('channel', 20)->default('web')->index()->after('event_type');
            $table->string('status', 20)->default('success')->index()->after('channel');
            $table->string('route_name', 150)->nullable()->after('request_id');
            $table->string('http_method', 10)->nullable()->after('route_name');
            $table->string('previous_hash', 64)->nullable()->after('http_method');
            $table->string('event_hash', 64)->nullable()->unique()->after('previous_hash');

            $table->index(['actor_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });

        $previousHash = null;
        foreach (DB::table('activity_events')->orderBy('id')->get() as $event) {
            $metadata = is_string($event->metadata) ? json_decode($event->metadata, true) : ($event->metadata ?? []);
            $payload = Arr::sortRecursive([
                'actor_id' => $event->actor_id,
                'user_session_id' => null,
                'event_type' => $event->event_type,
                'channel' => $event->channel,
                'status' => $event->status,
                'auditable_type' => $event->auditable_type,
                'auditable_id' => $event->auditable_id,
                'project_id' => $event->project_id,
                'client_id' => $event->client_id,
                'metadata' => $metadata,
                'created_at' => Carbon::parse($event->created_at)->format('Y-m-d H:i:s'),
                'previous_hash' => $previousHash,
            ]);
            $eventHash = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string) config('app.key'));

            DB::table('activity_events')->where('id', $event->id)->update([
                'previous_hash' => $previousHash,
                'event_hash' => $eventHash,
            ]);
            $previousHash = $eventHash;
        }
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['actor_id', 'created_at']);
            $table->dropIndex(['project_id', 'created_at']);
            $table->dropIndex(['event_type', 'created_at']);
            $table->dropIndex(['user_session_id']);
            $table->dropColumn([
                'user_session_id',
                'channel',
                'status',
                'route_name',
                'http_method',
                'previous_hash',
                'event_hash',
            ]);
        });
    }
};
