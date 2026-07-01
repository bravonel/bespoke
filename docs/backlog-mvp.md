# Backlog MVP

## 1. Enfoque

El backlog esta organizado para entregar valor usable desde temprano:

1. primero orden operativo
2. luego automatizacion de revision
3. despues portal cliente, realtime y bot

## 2. Fase 0: Discovery y base

### Objetivo

Alinear proceso real, lenguaje comun y base tecnica.

### Historias

- como direccion quiero definir catalogos base para que el sistema use el mismo lenguaje que la agencia
- como cuentas quiero contar con plantillas de proyecto por tipo de trabajo
- como admin quiero definir roles y permisos iniciales
- como equipo quiero acordar estados, semaforos y severidades

### Entregables

- mapa de proceso actual
- catalogos base
- blueprint de roles
- definicion de MVP cerrada

## 3. Fase 1: Core de aplicacion

### Objetivo

Tener acceso, navegacion base y entidades centrales.

### Historias

- como usuario quiero iniciar sesion de forma simple
- como admin quiero gestionar usuarios, clientes y marcas
- como usuario quiero navegar un dashboard base
- como sistema quiero registrar actividad relevante

### Entregables

- auth
- layout
- roles
- clientes
- marcas
- actividad

## 4. Fase 2: Modulo de proyectos MVP

### Objetivo

Operar proyectos reales dentro del sistema.

### Historias criticas

- como cuentas quiero crear un proyecto desde plantilla para no empezar de cero
- como PM quiero asignar responsables y fechas
- como colaborador quiero ver mis pendientes de hoy
- como equipo quiero mover tareas por etapas de forma rapida
- como usuario quiero comentar y adjuntar archivos en contexto
- como responsable quiero pedir aprobacion interna
- como cliente quiero revisar y aprobar sin depender de cadenas de correo

### Entregables

- proyectos
- plantillas
- tareas
- tablero kanban
- lista personal
- comentarios
- archivos
- aprobaciones
- digest diario

### Criterios de aceptacion

- se puede llevar al menos un proyecto completo dentro del sistema
- cada usuario ve pendientes asignados y vencidos
- hay historial de quien aprobo o rechazo

## 5. Fase 3: Notificaciones y seguimiento

### Objetivo

Reducir necesidad de persecucion manual.

### Historias

- como cuentas quiero recibir alerta de tareas vencidas
- como colaborador quiero recordatorios claros por canal
- como direccion quiero un resumen semanal de riesgos

### Entregables

- scheduler
- reglas de recordatorios
- notificaciones por correo
- notificaciones por WhatsApp
- reporte semanal

## 6. Fase 4: Modulo de claims MVP

### Objetivo

Pre-revisar materiales antes de revision regulatoria.

### Historias criticas

- como medico quiero subir estudios cientificos por marca
- como medico quiero registrar claims aprobados y referencias
- como diseno quiero subir un PDF para revision automatica
- como regulatorio quiero ver hallazgos clasificados por severidad
- como equipo quiero rerun de revision tras una correccion

### Entregables

- expedientes cientificos
- carga de estudios
- carga de claims
- carga de materiales PDF
- OCR y extraccion
- matching de claims
- validacion de referencias
- revision ortografica
- reporte de hallazgos

### Criterios de aceptacion

- el sistema detecta claims faltantes o dudosos
- el sistema muestra referencias encontradas o ausentes
- la revision genera un semaforo comprensible

## 7. Fase 5: Vista cliente y control de version

### Objetivo

Compartir de forma segura sin sacar el proceso del sistema.

### Historias

- como cliente quiero revisar materiales visibles para mi marca
- como cliente quiero comentar o aprobar
- como equipo quiero conservar historial de versiones y revisiones

### Entregables

- portal cliente
- permisos acotados
- versionado de materiales
- trazabilidad de comentarios

## 8. Fase 6: Mejoras posteriores

### Ideas para despues del MVP

- bot conversacional por proyecto
- dashboard de carga operativa
- analitica de retrabajo
- plantillas inteligentes con IA
- realtime con Reverb
- PWA avanzada
- modulo financiero
- modulo de minutas y juntas

## 9. Priorizacion MoSCoW

### Must

- auth
- usuarios
- clientes
- marcas
- proyectos
- tareas
- pendientes personales
- aprobaciones
- estudios
- claims
- materiales PDF
- revision automatica basica
- hallazgos y semaforo

### Should

- WhatsApp
- portal cliente
- versionado visible
- resumen semanal

### Could

- realtime
- busqueda avanzada
- dashboard ejecutivo profundo

### Wont yet

- facturacion
- app movil nativa
- motor regulatorio totalmente automatico

## 10. Plan sugerido de entregas

### Sprint 1

- auth, layout, usuarios, clientes, marcas

### Sprint 2

- proyectos, plantillas, tareas, lista personal

### Sprint 3

- comentarios, archivos, aprobaciones, digest diario

### Sprint 4

- expedientes cientificos, estudios, claims, referencias

### Sprint 5

- materiales PDF, OCR, matching, hallazgos

### Sprint 6

- reportes, portal cliente inicial, endurecimiento y QA
