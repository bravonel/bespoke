# PRD técnico P0 - Identity, Roles & Audit

## Bespoke-OS

Estado: listo para implementación
Fecha: 16 de julio de 2026
Owner: Bespoke-OS

## 1. Resultado buscado

Introducir autorización por rol, membresía de proyecto, acceso externo acotado y auditoría transversal antes de construir Evidence Hub, Pre-MLR y portal cliente.

El resultado debe conservar el uso interno actual de clientes, marcas, proyectos, tareas, capacidad y asistente IA.

## 2. Problema actual

- Las rutas operativas requieren autenticación, pero no autorización fina.
- `users` no tiene rol funcional de negocio.
- No existe membresía de proyecto.
- No existe acceso restringido por cliente/marca para revisores externos.
- La auditoría actual cubre consultas del asistente, no todas las acciones sensibles.
- Agregar claims, estudios y materiales sin esta base expondría riesgo de acceso indebido y decisiones no trazables.

## 3. Alcance P0

### 3.1 Roles internos

- `admin`
- `direction`
- `accounts`
- `traffic_pm`
- `medical`
- `design`
- `legal_regulatory`

### 3.2 Rol externo

- `client_reviewer`

### 3.3 Membresía de proyecto

- Asociar usuarios internos a proyectos.
- Rol dentro del proyecto.
- Estado activo/inactivo.
- Owner del proyecto permanece como relación existente.

### 3.4 Acceso externo

- Asociar `client_reviewer` a clientes, marcas y/o proyectos concretos.
- No mostrar notas internas, capacidad, otros clientes ni administración.
- Preparar el modelo; la UI completa del portal queda fuera de P0.

### 3.5 Auditoría

- Evento append-only.
- Actor, acción, entidad, entidad padre, cambios relevantes, IP/request metadata limitada y timestamp.
- Consultable por admin y por usuarios autorizados dentro del proyecto.

## 4. Fuera de alcance

- Portal cliente completo.
- Firma electrónica avanzada.
- SSO.
- Roles configurables por cliente.
- Permisos a nivel de campo.
- Evidence Hub y Pre-MLR.
- Notificaciones por WhatsApp.
- Impersonation.

## 5. Matriz de permisos P0

### admin

- Acceso total.
- Gestionar usuarios, roles, clientes y marcas.
- Ver auditoría global.

### direction

- Ver todos los proyectos y capacidad.
- Ver analítica operativa.
- No gestionar seguridad salvo permiso explícito futuro.

### accounts

- Crear/editar proyectos autorizados.
- Gestionar tareas, entregables y solicitudes de aprobación futuras.
- Ver clientes/marcas relacionados.

### traffic_pm

- Planear carga, tareas, responsables y fechas.
- Ver capacidad operativa.
- No tomar decisiones médicas/regulatorias.

### medical

- Ver proyectos asignados.
- Preparar/aprobar evidencia y claims cuando existan esos módulos.
- Disponer hallazgos médicos en fase posterior.

### design

- Ver proyectos/tareas asignados.
- Subir nuevas versiones futuras.
- Responder observaciones.
- No aprobar claims ni revisión regulatoria.

### legal_regulatory

- Ver proyectos asignados.
- Tomar decisiones regulatorias futuras.
- Cerrar hallazgos autorizados.

### client_reviewer

- Ver únicamente recursos explícitamente compartidos.
- Comentar/aprobar versiones futuras según invitación.
- No ver operación interna, capacidad, otros clientes, prompts IA ni notas privadas.

## 6. Modelo de datos

### users - cambios

- `role`
- `status` o reutilizar `is_active`
- conservar `area` y `puesto` como datos descriptivos, no como autorización.

### project_members

- `id`
- `project_id`
- `user_id`
- `project_role`
- `status`
- `added_by`
- timestamps
- unique (`project_id`, `user_id`)

### client_user_access

- `id`
- `client_id`
- `user_id`
- `access_level`
- timestamps
- unique (`client_id`, `user_id`)

### brand_user_access

- `id`
- `brand_id`
- `user_id`
- `access_level`
- timestamps
- unique (`brand_id`, `user_id`)

### project_user_access

Puede evitarse si `project_members` soporta al revisor externo y su nivel de acceso. Recomendación: usar una sola tabla `project_members` y distinguir `project_role` para reducir complejidad.

### activity_events

- `id`
- `actor_id` nullable para eventos del sistema
- `event_type`
- `auditable_type`
- `auditable_id`
- `project_id` nullable
- `client_id` nullable
- `metadata` JSON saneado
- `ip_hash` nullable
- `request_id` nullable
- `created_at`

No permitir update/delete desde la aplicación normal.

## 7. Estrategia de migración de usuarios

El sistema tiene usuarios internos existentes. La migración debe evitar bloqueo accidental.

### Regla recomendada

1. Agregar `role` nullable.
2. Asignar `admin` al usuario definido por configuración de despliegue o comando explícito.
3. Asignar roles al resto mediante comando interactivo/admin antes de hacer obligatorio el campo.
4. Durante rollout controlado, un usuario legacy sin rol recibe acceso interno equivalente al actual y genera un warning de auditoría.
5. Después de completar la asignación, hacer `role` obligatorio y retirar el fallback.

No inferir autorización únicamente desde `area` o `puesto`.

## 8. Policies requeridas

- `ClientPolicy`
- `BrandPolicy`
- `ProjectPolicy`
- `TaskPolicy`
- `CollaboratorPolicy`
- `ActivityEventPolicy`
- Policies futuras para archivos, evidencia, materiales y reviews.

### Reglas base

- Admin puede todo.
- Dirección puede ver operación interna completa.
- Usuario interno no-admin accede por responsabilidad o membresía.
- Revisor externo sólo por grant/membresía explícita.
- Toda query de detalle debe autorizar la entidad, no sólo ocultar navegación.

## 9. Integración con rutas actuales

Las rutas permanecen bajo `auth` y `TrackUserActivity`.

Agregar autorización mediante:

- Policies en controladores.
- Form requests cuando aplique.
- Navegación condicionada como apoyo visual, nunca como única seguridad.

No crear rutas duplicadas para cada rol.

## 10. Eventos mínimos de auditoría

### Identidad y acceso

- user.created/updated/activated/deactivated
- user.role_changed
- project.member_added/role_changed/removed
- client_access.granted/revoked

### Operación

- client.created/updated/deleted
- brand.created/updated/deleted
- project.created/updated/status_changed/deleted
- task.created/updated/status_changed/moved/deleted
- subtask.created/updated/deleted
- capacity.updated

### IA

Mantener `ai_assistant_messages` como auditoría detallada del módulo IA y emitir además un evento resumido:

- ai.query.completed/failed
- ai.speech.generated/failed

Nunca guardar API keys, tokens, passwords ni audio sensible en metadata.

## 11. Servicio de auditoría

Crear una API interna única, por ejemplo:

- `AuditLogger::record(...)`
- eventos/listeners para cambios de dominio cuando aporte claridad.

Evitar dispersar arrays de metadata incompatibles en controladores.

El logger debe:

- aceptar actor nullable;
- sanear metadata;
- asociar proyecto/cliente cuando exista;
- no interrumpir una operación crítica por un fallo secundario sin dejar alerta;
- soportar tests sin depender de servicios externos.

## 12. UI mínima

### Gestión de colaboradores

- Agregar rol.
- Explicar diferencia entre rol, área y puesto.
- Impedir desactivar al último admin.
- Mostrar acceso activo/inactivo.

### Proyecto

- Sección de miembros.
- Agregar/quitar usuario.
- Rol dentro del proyecto.
- Actividad reciente del proyecto.

### Auditoría

- Timeline en proyecto.
- Vista global sólo admin/direction.
- Filtros por actor, evento, cliente, proyecto y fecha.
- Metadata legible; no mostrar JSON crudo como experiencia principal.

## 13. Criterios de aceptación

- Todos los usuarios existentes conservan acceso durante rollout.
- Existe al menos un admin confirmado antes de activar enforcement.
- No se puede desactivar o degradar al último admin.
- Un usuario interno no autorizado no puede abrir un proyecto por URL.
- Un `client_reviewer` no puede listar ni consultar otros clientes/proyectos.
- Las mutaciones de clientes, marcas, proyectos, tareas, capacidad y usuarios generan evento.
- Los eventos son append-only desde la aplicación.
- La auditoría no contiene secretos ni passwords.
- Cambiar un rol invalida el acceso en la siguiente petición.
- El asistente IA sólo recibe contexto autorizado para el usuario.

## 14. Pruebas requeridas

### Agregar

- `RoleMigrationTest`
- `LastAdminProtectionTest`
- `ProjectPolicyTest`
- `ClientReviewerIsolationTest`
- `ProjectMembershipTest`
- `ActivityEventAppendOnlyTest`
- `AuditMetadataSanitizationTest`
- `AiContextAuthorizationTest`

### Mantener

- Auth.
- CollaboratorManagementTest.
- ProjectBoardTest.
- AiAssistantTest.
- Catalog tests.

### QA manual

- Admin.
- Dirección.
- Cuentas asignado/no asignado.
- Diseño asignado/no asignado.
- Revisor externo con un solo proyecto.
- Cambio de rol durante sesión.
- Desactivación de usuario.

## 15. Rollout

### Fase A - Schema y auditoría pasiva

- Agregar roles/membresías/eventos.
- Registrar acciones sin restringir comportamiento legacy.
- Asignar roles reales.

### Fase B - Policies internas

- Activar autorización para colaboradores, clientes, marcas y proyectos.
- Verificar operación diaria.

### Fase C - Acceso externo controlado

- Crear un revisor de prueba.
- Compartir un proyecto sin documentos sensibles reales.
- Probar aislamiento antes de desarrollar portal.

## 16. Definition of Done

- Migraciones reversibles.
- Roles asignados y último admin protegido.
- Policies aplicadas a lectura y mutación.
- Auditoría append-only y saneada.
- Tests de aislamiento pasando.
- Sin regresión en overview, proyectos, tareas o asistente.
- Navegación móvil/desktop coherente.
- README/arquitectura actualizados.
- `composer test` verde o fallos documentados y no relacionados.

## 17. Orden de implementación

1. Agregar roles compatibles y comando de asignación.
2. Proteger último admin.
3. Crear project_members.
4. Crear activity_events y AuditLogger.
5. Instrumentar mutaciones actuales.
6. Crear policies.
7. Autorizar AiContextBuilder.
8. UI de rol en colaboradores.
9. UI de miembros/actividad en proyecto.
10. Pruebas de aislamiento y rollout.

## 18. Dependencias para módulos siguientes

Este P0 desbloquea:

- File & Version Hub.
- Evidence Hub.
- Pre-MLR Review Engine.
- Human Review Workspace.
- Client Portal.

Ninguno de esos módulos debe abrir acceso externo antes de completar los criterios de aislamiento de este PRD.
