<?php

namespace App\Support;

use Illuminate\Support\Str;

final class OperationalLabels
{
    /**
     * Translate stored operational codes into user-facing Spanish labels.
     */
    public static function get(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::labels()[$value] ?? Str::of($value)->replace('_', ' ')->headline()->toString();
    }

    public static function labels(): array
    {
        return [
            'active' => 'Activo',
            'archived' => 'Archivado',
            'blocked' => 'Bloqueado',
            'brief' => 'Resumen inicial',
            'client_review' => 'Revisión cliente',
            'critical' => 'Crítica',
            'design' => 'Diseño',
            'done' => 'Listo',
            'draft' => 'Borrador',
            'high' => 'Alta',
            'in_progress' => 'En proceso',
            'in_review' => 'En revisión',
            'low' => 'Baja',
            'medical_review' => 'Revisión médica',
            'normal' => 'Normal',
            'on_hold' => 'En pausa',
            'paused' => 'Pausado',
            'ready_to_submit' => 'Listo para enviar',
            'todo' => 'Por hacer',
        ];
    }
}
