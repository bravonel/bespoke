<?php

namespace App\Console\Commands;

use App\Models\ActivityEvent;
use App\Services\Audit\ActivityChainVerifier;
use App\Services\Audit\ActivityEventTriggerManager;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RepairLegacyActivityContext extends Command
{
    protected $signature = 'activity:repair-legacy-context
        {--apply : Aplica únicamente reparaciones criptográficamente comprobadas}
        {--backup-confirmed : Confirma que existe un respaldo reciente y verificable}
        {--max-project-id=10000 : Límite superior de IDs históricos a comprobar}';

    protected $description = 'Recupera project_id nulificados por llaves foráneas antiguas sin refirmar eventos';

    public function handle(
        ActivityChainVerifier $verifier,
        ActivityEventTriggerManager $triggers,
        AuditLogger $audit,
    ): int {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY no está configurada; se cancela la reparación.');

            return self::FAILURE;
        }

        $maxProjectId = (int) $this->option('max-project-id');
        if ($maxProjectId < 1 || $maxProjectId > 1000000) {
            $this->error('--max-project-id debe estar entre 1 y 1000000.');

            return self::FAILURE;
        }

        $repairs = [];
        $ambiguous = [];

        foreach (ActivityEvent::query()->whereNull('project_id')->orderBy('id')->cursor() as $event) {
            if (hash_equals((string) $event->event_hash, $verifier->expectedHash($event))) {
                continue;
            }

            $matches = [];
            for ($candidate = 1; $candidate <= $maxProjectId; $candidate++) {
                if (hash_equals(
                    (string) $event->event_hash,
                    $verifier->expectedHash($event, $candidate, true),
                )) {
                    $matches[] = $candidate;
                }

                if (count($matches) > 1) {
                    break;
                }
            }

            if (count($matches) === 1) {
                $repairs[] = ['event_id' => $event->id, 'project_id' => $matches[0]];
            } else {
                $ambiguous[] = ['event_id' => $event->id, 'matches' => count($matches)];
            }
        }

        $this->table(['Evento', 'project_id comprobado'], array_map(
            fn (array $repair) => [$repair['event_id'], $repair['project_id']],
            $repairs,
        ));

        if ($ambiguous !== []) {
            $this->warn(count($ambiguous).' evento(s) alterado(s) no tienen una recuperación única dentro del rango; no se tocarán.');
        }

        if (! $this->option('apply')) {
            $this->info('Simulación terminada. No se modificó ningún evento.');

            return self::SUCCESS;
        }

        if (! $this->option('backup-confirmed')) {
            $this->error('Para aplicar, confirma el respaldo con --backup-confirmed.');

            return self::FAILURE;
        }

        if ($repairs === []) {
            $this->info('No hay reparaciones comprobadas por aplicar.');

            return $ambiguous === [] ? self::SUCCESS : self::FAILURE;
        }

        $triggers->dropUpdateProtection();

        try {
            DB::transaction(function () use ($repairs): void {
                foreach ($repairs as $repair) {
                    DB::table('activity_events')
                        ->where('id', $repair['event_id'])
                        ->whereNull('project_id')
                        ->update(['project_id' => $repair['project_id']]);
                }
            });
        } catch (Throwable $exception) {
            $this->error('La reparación falló: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $triggers->restoreUpdateProtection();
        }

        $remainingFailures = $verifier->failures();
        if ($remainingFailures !== []) {
            $this->error('La cadena todavía presenta fallos; no se registró cierre de remediación.');

            return self::FAILURE;
        }

        $audit->recordSystem('activity.legacy_context_repaired', metadata: [
            'event_ids' => array_column($repairs, 'event_id'),
            'restored_fields' => ['project_id'],
            'repair_method' => 'unique_hmac_match',
        ], channel: 'system');

        $this->info(count($repairs).' evento(s) reparado(s). Cadena válida y protección restaurada.');

        return self::SUCCESS;
    }
}
