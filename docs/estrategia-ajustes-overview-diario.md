# Estrategia de ajustes: overview diario por usuario

## 1. Lectura del feedback del cliente

Fuente principal: `Privada 2a. de Arizona 3.m4a`, transcrito localmente con `whisper.cpp`.
Fuente de referencia: `STATUS BESPOKE.xlsx`.

El cliente describe el uso actual del Excel como una vista diaria de operación:

- El status se organiza por laboratorio y dentro de cada laboratorio por marcas.
- Cada proyecto/material tiene un número de ODT, útil para seguimiento.
- A cada tarea se le asigna área o persona, por ejemplo Arte o Copy.
- También se registra cuántas horas se le van a dar a esa tarea en el día.
- El archivo se revisa diario en la mañana para que cada persona sepa qué debe hacer.
- A las 6:00 pm se revisa qué se terminó y qué se pasa al siguiente día.
- La solicitud concreta es que la app tenga, en el overview, un resumen por usuario con las tareas asignadas para el día y las horas estimadas para trabajarlas.

Conclusión: el ajuste no es sobre el video. Es una mejora funcional para que Bespoke OS sustituya el uso operativo diario del Excel.

## 2. Lo que confirma el Excel

La hoja `STATUS GENERAL` usa esta estructura base:

- `Laboratorio`
- `Marca`
- `ODT`
- `Fecha de solicitud`
- `Proyecto`
- `Área`
- `Responsable`
- `Status`
- `Fecha`
- `Tiempos (horas)`
- `Duración (días)`

La hoja `CERRADOS` usa una estructura equivalente con `LAB`, `MARCA`, `ODT`, `PROYECTO`, `ÁREA`, `RESPONSABLE`, `PROCESO`, `FECHA DE ENTREGA`, `TIEMPO (HORAS)` y `DURACIÓN (DÍAS)`.

Esto valida que el dato clave faltante en la app no es solo "responsable", sino planeación diaria de capacidad: fecha de trabajo + horas estimadas + responsable.

## 3. Brecha en la app actual

La app ya tiene:

- Usuarios con `area` y `puesto`.
- Proyectos con cliente, marca, responsable, prioridad, status y fecha compromiso.
- Tareas con `assigned_to`, `status`, `priority`, `due_at` y subtareas.
- Vista `Mis tareas`.
- Dashboard operativo con métricas generales y actividad reciente.

La app todavía no tiene:

- Horas estimadas por tarea.
- Fecha específica de trabajo diario separada de la fecha compromiso.
- Resumen de carga por usuario.
- Total de horas asignadas por persona para hoy.
- Flujo para pasar tareas no terminadas al siguiente día.
- Campo ODT importable desde el Excel.

## 4. Objetivo del ajuste

Convertir el overview en una vista de tráfico diario:

> "Quién tiene qué asignado hoy, cuántas horas suma, qué está vencido o bloqueado, y qué debe revisarse al cierre del día."

Este ajuste debe mantener la app simple. No debe convertirse todavía en timesheet ni control financiero. Las horas son planeadas, no horas reales capturadas.

## 5. Alcance MVP recomendado

### 5.1 Datos nuevos

Agregar a `tasks`:

- `planned_for`: fecha en la que se planea trabajar la tarea.
- `estimated_minutes`: esfuerzo estimado en minutos.

Opcional, pero conveniente para importar Excel:

- `external_reference` o `odt_code` en `projects`, para guardar el ODT del cliente cuando exista.

Más adelante:

- `daily_capacity_minutes` en `users`, para comparar horas asignadas contra capacidad real por persona.

### 5.2 Captura en tareas

En el modal de nueva tarea y edición de tarea:

- Mantener `Fecha compromiso` como fecha límite.
- Agregar `Fecha de trabajo`.
- Agregar `Horas estimadas`.
- Mostrar advertencia suave cuando falten horas o responsable.

Regla simple:

- Si no hay `planned_for`, usar `due_at` como fallback visual.
- Para cálculo de carga diaria, solo contar tareas con `planned_for = fecha seleccionada`.

### 5.3 Overview diario

Agregar en el dashboard una sección nueva: `Carga de hoy`.

Debe incluir:

- Selector de fecha, default hoy.
- Filtro por área.
- Filtro por usuario.
- Tabla o tarjetas por usuario con:
  - nombre
  - área/puesto
  - número de tareas asignadas
  - horas estimadas totales
  - tareas bloqueadas
  - tareas vencidas
  - tareas sin estimación
- Lista expandible de tareas por usuario:
  - cliente
  - marca
  - proyecto
  - ODT si existe
  - título de tarea
  - status
  - prioridad
  - horas estimadas
  - fecha compromiso

### 5.4 Vista personal

Ajustar `Mis tareas` para que tenga una sección principal:

- `Hoy`
- `Próximas`
- `Sin fecha de trabajo`
- `Listas`

En cada tarjeta mostrar horas estimadas.

### 5.5 Revisión de cierre

Agregar una acción rápida para tareas no terminadas:

- `Pasar a mañana`
- `Cambiar fecha de trabajo`
- `Marcar bloqueada`
- `Marcar lista`

Esto replica el ritual que el cliente describió: revisar al final del día qué se avanzó y qué se mueve.

## 6. Mapeo Excel a app

| Excel | App |
| --- | --- |
| Laboratorio / LAB | Cliente |
| Marca / MARCA | Marca |
| ODT | `projects.odt_code` o `projects.external_reference` |
| Proyecto | Proyecto o tarea, según granularidad |
| Área | Área del usuario o workstream |
| Responsable | Usuario asignado |
| Status / Proceso | Status de tarea |
| Fecha / Fecha de entrega | `due_at` o `planned_for`, según contexto |
| Tiempos (horas) | `estimated_minutes` |
| Duración (días) | referencia operativa, no prioridad para MVP |

## 7. Plan de implementación por fases

### Fase 1: Base de datos y captura

- Crear migración para `planned_for` y `estimated_minutes` en `tasks`.
- Agregar casts y fillable en `Task`.
- Actualizar validación de `TaskController`.
- Actualizar modal de creación y edición de tarea.
- Mostrar horas en tarjetas de proyecto y detalle de tarea.

Resultado: ya se puede capturar lo que hoy vive en `Tiempos (horas)`.

### Fase 2: Overview por usuario

- En `DashboardController`, crear query de tareas por `planned_for`.
- Agrupar por `assigned_to`.
- Calcular tareas totales, horas totales, bloqueadas, vencidas y sin estimación.
- Agregar sección `Carga de hoy` en `dashboard.blade.php`.

Resultado: dirección y cuentas pueden ver la carga diaria sin abrir cada proyecto.

### Fase 3: Mi día

- Ajustar `MyTasksController` para separar tareas de hoy, próximas y sin fecha.
- Mostrar total de horas estimadas del día.
- Mantener agrupación por status dentro de cada sección si no complica la UI.

Resultado: cada usuario ve qué debe hacer hoy y cuánto tiempo está planeado.

### Fase 4: Cierre diario

- Agregar acciones rápidas para cambiar `planned_for`.
- Crear botón `Pasar a mañana` para tareas abiertas.
- Agregar filtro de "pendientes de ayer" en overview.

Resultado: el ritual de revisión de 6 pm queda dentro de la app.

### Fase 5: Importación desde Excel

- Crear importador controlado para `STATUS BESPOKE.xlsx`.
- Primero importar clientes, marcas y ODT.
- Después importar proyectos/tareas abiertas.
- Mantener una tabla de aliases para responsables, porque el Excel usa nombres cortos como `Beto`, `mafer`, `Sony`, etc.

Resultado: se reduce captura manual y se conserva continuidad con el sistema actual.

## 8. Criterios de aceptación

El ajuste está listo cuando:

- Puedo crear una tarea con responsable, fecha de trabajo y horas estimadas.
- El overview muestra, por usuario, las tareas planeadas para la fecha seleccionada.
- El overview suma correctamente las horas por usuario.
- Las tareas sin responsable o sin horas quedan visibles.
- Las tareas bloqueadas y vencidas se distinguen del resto.
- `Mis tareas` muestra una sección clara de tareas de hoy.
- Una tarea no terminada se puede mover a mañana sin editar todo el proyecto.

## 9. Riesgos y decisiones pendientes

- La capacidad diaria estándar queda definida en 8 horas por persona, con posibilidad operativa de excedente visible como sobrecarga.
- La ODT u orden de compra queda definida como identificador operativo principal del proyecto.
- El Excel no se importará en esta fase; solo se toma como referencia de estructura y ritual operativo.
- Si más adelante se importa información, habrá que decidir si `Fecha` del Excel representa fecha de entrega, fecha de seguimiento o fecha de trabajo.
- Normalizar nombres de responsables antes de importar, porque el Excel mezcla nombres, apodos y mayúsculas.
- Evitar que el equipo sienta que debe capturar tiempos reales. Para esta fase, solo se capturan horas planeadas.

## 10. Recomendación

Implementar primero Fase 1 y Fase 2. Es el menor cambio con mayor valor operativo: agrega horas a tareas y convierte el dashboard en una vista diaria por usuario, que es exactamente lo que el cliente pidió en el audio.

## 11. Implementación inicial aplicada

- `users.daily_capacity_minutes`: capacidad diaria estándar, default 480 minutos.
- `projects.odt_code`: ODT / orden de compra visible como identificador operativo.
- `tasks.planned_for`: fecha de trabajo de la tarea.
- `tasks.estimated_minutes`: horas planeadas almacenadas en minutos.
- Dashboard: sección `Carga diaria` con filtros por fecha, área y usuario.
- Mis tareas: secciones `Hoy`, `Próximas`, `Sin fecha de trabajo` y `Listas`.
- Acción rápida: `Pasar a mañana` para tareas abiertas.
