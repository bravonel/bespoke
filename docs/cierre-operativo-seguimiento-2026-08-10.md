# Cierre operativo posterior a la revisión de Bespoke OS

Fecha de cierre técnico: 10 de agosto de 2026.

## Flujo operativo vigente

Cada tarea sigue este flujo:

1. **Por hacer**: asignada, con día de carga, horas estimadas y orden personal.
2. **En proceso**: registra el primer inicio, sin interpretar el tiempo transcurrido como horas efectivas.
3. **Bloqueado**: exige explicar el impedimento y quién debe resolverlo.
4. **Entregado**: exige que toda la lista esté resuelta; queda disponible para revisión interna.
5. **Finalizado**: sólo Cuentas, Tráfico, Dirección o el responsable general pueden confirmar la entrega final.

Una tarea entregada o finalizada sólo puede regresar al flujo activo si se registra qué debe corregirse. Cuando todas las tareas quedan finalizadas, el proyecto se cierra automáticamente. Si se reabre trabajo, el proyecto vuelve a estado activo.

## Responsabilidades y permisos

- Administración, Dirección, Cuentas y Tráfico/PM tienen panorama operativo global.
- El responsable general del proyecto puede editar proyecto, carga, fechas, horas, responsables y tareas.
- Diseño, Medical, Legal y otros colaboradores sólo ven proyectos relacionados con ellos.
- El responsable de una tarea puede moverla y completar su lista, pero no alterar horas, fechas, responsable ni orden de otras personas.
- La finalización queda reservada a gestión; “Entregado” no equivale a “Finalizado”.
- Los usuarios históricos sin rol mantienen temporalmente el acceso previo durante la asignación pasiva de roles.

## Información y archivos

- `Material de apoyo / OneDrive / SharePoint` continúa siendo una liga; Bespoke OS no sustituye el repositorio de archivos.
- Cada ODT debe apuntar a una sola carpeta operativa con permisos para el equipo involucrado.
- Diseño, Medical y Cuentas deben cargar sus versiones en esa carpeta para evitar ligas y copias dispersas.

## Criterios para retirar Excel

Antes del corte definitivo se debe comprobar con ODT reales:

- creación y asignación con prioridad personal;
- bloqueo y destrabe con motivo;
- entrega con checklist completo;
- devolución con instrucción de corrección;
- finalización y cierre automático del proyecto;
- visibilidad correcta por rol;
- carga diaria, vencimientos, exportación y consulta del asistente;
- acceso válido a la carpeta única del proyecto.

## Insumos externos todavía requeridos

Estos puntos no pueden cerrarse sólo con código porque requieren decisiones o credenciales del equipo:

- textos legales aprobados, clasificados por laboratorio y público;
- checklist resumido y aprobado por Luis y Ceci para cada tipo de material;
- números autorizados, plantilla y credenciales de Meta para notificaciones productivas de WhatsApp;
- elección del canal final de notificaciones y reglas para evitar alertas excesivas;
- asignación de rol a todas las cuentas históricas;
- fecha formal de corte de Excel y responsable de soporte durante adopción.

## Evidencia técnica

El flujo, la autorización y las regresiones se verifican en `tests/Feature/TaskWorkflowTest.php` junto con la suite general del proyecto.
