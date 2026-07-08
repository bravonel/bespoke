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
            'both' => 'Digital e impreso',
            'brief' => 'Resumen inicial',
            'brochure' => 'Folleto',
            'campaign' => 'Campaña',
            'campana' => 'Campaña',
            'campaña' => 'Campaña',
            'client_review' => 'Revisión del cliente',
            'Copy' => 'Redacción',
            'copy' => 'Redacción',
            'Copy / Proofreader' => 'Redacción / Corrección',
            'critical' => 'Crítica',
            'Cuentas' => 'Cuentas',
            'design' => 'Diseño',
            'Design' => 'Diseño',
            'Dirección General' => 'Dirección General',
            'Dirección general' => 'Dirección general',
            'digital' => 'Digital',
            'Digital' => 'Digital',
            'done' => 'Listo',
            'draft' => 'Borrador',
            'flyer' => 'Volante',
            'Animador' => 'Animador',
            'Director de Arte' => 'Director de Arte',
            'Diseñador Jr.' => 'Diseñador Jr.',
            'Diseñador Sr.' => 'Diseñador Sr.',
            'Diseñador Web' => 'Diseñador Web',
            'Gerente de innovación' => 'Gerente de innovación',
            'Gestor de comunidad' => 'Gestor de comunidad',
            'Gestor de proyectos' => 'Gestor de proyectos',
            'high' => 'Alta',
            'in_progress' => 'En proceso',
            'in_review' => 'En revisión',
            'Innovation Manager' => 'Gerente de innovación',
            'low' => 'Baja',
            'material' => 'Material',
            'Médico' => 'Médico',
            'medical' => 'Médico',
            'Medical' => 'Médico',
            'medical_review' => 'Revisión médica',
            'Medical Writer' => 'Redactor médico',
            'monografia' => 'Monografía',
            'monografía' => 'Monografía',
            'normal' => 'Normal',
            'on_hold' => 'En pausa',
            'printed' => 'Impreso',
            'Project Manager' => 'Gestor de proyectos',
            'paused' => 'Pausado',
            'ready_to_submit' => 'Listo para enviar',
            'Redacción' => 'Redacción',
            'Redacción / Corrección' => 'Redacción / Corrección',
            'Redactor médico' => 'Redactor médico',
            'Redes sociales' => 'Redes sociales',
            'Responsable de redes sociales' => 'Responsable de redes sociales',
            'Social Media' => 'Redes sociales',
            'social_media' => 'Redes sociales',
            'Social Media Manager' => 'Responsable de redes sociales',
            'Community Manager' => 'Gestor de comunidad',
            'visual_aid' => 'Ayuda visual',
            'ayuda_visual' => 'Ayuda visual',
            'folleto' => 'Folleto',
            'presentacion' => 'Presentación',
            'presentación' => 'Presentación',
            'video' => 'Video',
            'otro' => 'Otro',
            'todo' => 'Por hacer',
        ];
    }
}
