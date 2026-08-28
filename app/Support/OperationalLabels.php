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
            'both' => 'Digital e impreso',
            'brief' => 'Inicial',
            'initial' => 'Inicial',
            'brochure' => 'Folleto',
            'campaign' => 'Campaña',
            'campana' => 'Campaña',
            'campaña' => 'Campaña',
            'client_review' => 'Cliente',
            'client' => 'Cliente',
            'Copy' => 'Copy',
            'copy' => 'Copy',
            'Copy / Proofreader' => 'Copy / Corrección',
            'critical' => 'Crítica',
            'Cuentas' => 'Cuentas',
            'design' => 'Diseño',
            'Design' => 'Diseño',
            'Dirección General' => 'Dirección General',
            'Dirección general' => 'Dirección general',
            'digital' => 'Digital',
            'Digital' => 'Digital',
            'done' => 'Entregado',
            'finalized' => 'Finalizado',
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
            'inactive' => 'Inactivo',
            'low' => 'Baja',
            'material' => 'Material',
            'Médico' => 'Médico',
            'medical' => 'Medical',
            'Medical' => 'Medical',
            'medical_review' => 'Medical',
            'Medical Writer' => 'Redactor médico',
            'monografia' => 'Monografía',
            'monografía' => 'Monografía',
            'normal' => 'Normal',
            'on_hold' => 'En pausa',
            'printed' => 'Impreso',
            'Project Manager' => 'Gestor de proyectos',
            'paused' => 'Pausado',
            'ready_to_submit' => 'Cliente',
            'Redacción' => 'Copy',
            'Redacción / Corrección' => 'Copy / Corrección',
            'Redactor médico' => 'Redactor médico',
            'Redes sociales' => 'Social Media',
            'Responsable de redes sociales' => 'Responsable de redes sociales',
            'Social Media' => 'Social Media',
            'social_media' => 'Social Media',
            'accounts' => 'Cuentas',
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
