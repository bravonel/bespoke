<?php

namespace App\Services\Activity;

use Illuminate\Support\Str;

class ActivityLabels
{
    private const LABELS = [
        'auth.login_succeeded' => 'Inicio de sesión',
        'auth.login_failed' => 'Inicio fallido',
        'auth.locked_out' => 'Acceso bloqueado',
        'auth.logout' => 'Cierre de sesión',
        'auth.session_expired' => 'Sesión expirada',
        'auth.session_revoked' => 'Sesión revocada',
        'client.created' => 'Cliente creado',
        'client.updated' => 'Cliente actualizado',
        'client.status_changed' => 'Estatus de cliente cambiado',
        'client.deleted' => 'Cliente eliminado',
        'brand.created' => 'Marca creada',
        'brand.updated' => 'Marca actualizada',
        'brand.status_changed' => 'Estatus de marca cambiado',
        'brand.deleted' => 'Marca eliminada',
        'project.created' => 'Proyecto creado',
        'project.updated' => 'Proyecto actualizado',
        'project.status_changed' => 'Estatus de proyecto cambiado',
        'project.stage_changed' => 'Etapa de proyecto cambiada',
        'project.deleted' => 'Proyecto eliminado',
        'project.member_added' => 'Miembro agregado al proyecto',
        'project.member_role_changed' => 'Rol de proyecto cambiado',
        'project.member_removed' => 'Miembro retirado del proyecto',
        'project.workload_added' => 'Carga agregada',
        'project.workload_changed' => 'Carga modificada',
        'project.workload_removed' => 'Carga eliminada',
        'task.created' => 'Tarea creada',
        'task.updated' => 'Tarea actualizada',
        'task.assigned' => 'Tarea reasignada',
        'task.status_changed' => 'Estatus de tarea cambiado',
        'task.schedule_changed' => 'Fechas de tarea cambiadas',
        'task.reordered' => 'Tarea reordenada',
        'task.deleted' => 'Tarea eliminada',
        'subtask.created' => 'Subtarea creada',
        'subtask.completed' => 'Subtarea completada',
        'subtask.reopened' => 'Subtarea reabierta',
        'subtask.deleted' => 'Subtarea eliminada',
        'activity.center_viewed' => 'Centro de actividad consultado',
        'report.exported' => 'Reporte exportado',
        'activity.alert_resolved' => 'Alerta de actividad resuelta',
        'user.capacity_changed' => 'Capacidad diaria cambiada',
        'ai.question_asked' => 'Pregunta a IA',
        'ai.answer_completed' => 'Respuesta de IA',
        'ai.answer_failed' => 'Error de IA',
    ];

    public static function get(string $event): string
    {
        return self::LABELS[$event]
            ?? Str::of($event)->replace(['.', '_'], ' ')->headline()->toString();
    }
}
