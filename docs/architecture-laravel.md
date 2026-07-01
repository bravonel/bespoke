# Arquitectura Laravel

## 1. Decision principal

Construir `Bespoke OS` como un `monolito modular` sobre Laravel.

Esto reduce costo, acelera salida a produccion y mantiene una sola base de codigo para:

- autenticacion
- permisos
- archivos
- modulos
- notificaciones
- auditoria
- automatizaciones

## 2. Stack recomendado

### Base

- `PHP 8.3+`
- `Laravel 13`
- `PostgreSQL`
- `Redis`
- `Nginx`

### UI

- `Blade`
- `Livewire 4`
- `Tailwind CSS`
- `Alpine.js` solo para microinteracciones necesarias

### Laravel ecosystem

- `Queues` para OCR, parsing y revisiones
- `Scheduler` para recordatorios y digests
- `Notifications` para correo y WhatsApp
- `Horizon` para monitoreo de colas
- `Scout` con driver `database` para busqueda inicial
- `Pennant` para flags de modulos y despliegues graduales
- `Pulse` para observabilidad basica
- `Reverb + Echo` en fase 2 si se requiere colaboracion en vivo

## 3. Por que Livewire

Livewire permite construir una interfaz muy interactiva sin el costo de una SPA compleja.

Encaja especialmente bien para:

- tableros
- formularios largos
- filtros
- comentarios
- aprobaciones
- listas personales
- modales
- paneles de revision

## 4. Principios de arquitectura

- `modular por dominio`, no por tipo tecnico
- `colas para cualquier proceso pesado`
- `reglas deterministas primero, IA despues`
- `historial completo de acciones`
- `pocas dependencias premium al inicio`

## 5. Modulos de aplicacion

### Core

- usuarios
- roles
- clientes
- marcas
- equipos
- archivos
- actividad
- notificaciones

### Projects

- proyectos
- etapas
- tareas
- entregables
- comentarios
- bloqueos
- aprobaciones

### Claims Review

- expedientes cientificos
- estudios
- claims aprobados
- referencias
- materiales
- corridas de revision
- hallazgos
- reportes

### Shared Services

- OCR
- parser de PDFs
- motor de matching de claims
- corrector de redaccion
- motor de notificaciones

## 6. Estructura sugerida de codigo

```text
app/
  Domain/
    Core/
    Projects/
    Claims/
  Actions/
  Livewire/
  Models/
  Policies/
  Notifications/
  Jobs/
  Services/
  Support/
```

### Criterio

- `Domain/` contiene reglas de negocio
- `Livewire/` contiene componentes de interfaz
- `Jobs/` procesa OCR, analisis y digests
- `Services/` integra proveedores externos
- `Policies/` controla acceso fino

## 7. Integraciones externas recomendadas

### Correo

- `Resend` o `Postmark`
- criterio: simple, transaccional y facil de operar

### WhatsApp

- `Twilio WhatsApp`
- criterio: recordatorios, aprobaciones rapidas y avisos

### OCR / lectura documental

- proveedor externo de OCR para PDFs
- criterio: buena extraccion de texto y estructura

### IA

- proveedor de LLM para:
  - matching semantico de claims
  - revision de redaccion
  - resumenes por proyecto
  - asistente conversacional futuro

## 8. Uso de IA dentro del sistema

La IA debe operar como `asistente`, no como autoridad final.

### Casos de uso V1

- comparar claim esperado contra texto encontrado
- señalar frases con riesgo de redaccion
- resumir hallazgos
- construir resumen diario por proyecto

### Casos que no deben automatizarse al 100 por ciento

- decision regulatoria final
- interpretacion cientifica delicada
- liberacion de materiales

## 9. Modelo de interaccion

### Pantallas clave

- `Hoy`
- `Mis pendientes`
- `Proyectos`
- `Detalle del proyecto`
- `Revision de material`
- `Bandeja de aprobaciones`
- `Vista cliente`

### Movil primero

La interfaz debe priorizar:

- tocar una sola vez para actualizar estado
- ver responsable y siguiente paso sin abrir muchas pantallas
- aprobar o comentar desde movil

## 10. Eventos y jobs principales

### Eventos

- `ProjectCreated`
- `TaskAssigned`
- `TaskOverdue`
- `ApprovalRequested`
- `MaterialUploaded`
- `ReviewCompleted`
- `CriticalFindingDetected`

### Jobs

- `GenerateDailyDigest`
- `SendPendingReminders`
- `ExtractPdfText`
- `ParseScientificEvidence`
- `AnalyzeMaterialClaims`
- `ValidateReferences`
- `ReviewSpellingAndTone`
- `BuildReviewReport`

## 11. Seguridad y acceso

### Recomendacion inicial

- autenticacion con session de Laravel
- autorizacion con `Policies` + roles propios
- aislamiento por cliente y marca donde aplique
- URLs firmadas para acceso temporal a archivos compartidos
- auditoria de acciones sensibles

### No meter aun

- SSO empresarial
- permisos hipergranulares por campo

## 12. Almacenamiento

### Base de datos

- `PostgreSQL` como fuente principal

### Cache y colas

- `Redis`

### Archivos

- `S3 compatible storage`
- separar:
  - archivos operativos
  - estudios cientificos
  - materiales
  - reportes generados

## 13. Observabilidad

- `Horizon` para colas
- `Pulse` para salud de la app
- logs estructurados para jobs y errores de integracion

## 14. Despliegue recomendado

### Opcion costo-controlado

- `Laravel Forge`
- 1 VPS para app
- 1 base administrada pequena o en el mismo proveedor
- bucket S3 compatible

### Opcion mas comoda

- hosting administrado del ecosistema Laravel

## 15. Fases tecnicas

### Fase 1

- auth
- layout base
- roles
- clientes, marcas, proyectos
- tablero y pendientes
- notificaciones basicas

### Fase 2

- pipeline de documentos
- revision de claims
- hallazgos y reportes
- vista cliente

### Fase 3

- realtime con Reverb
- asistente conversacional
- analitica de operacion

## 16. Decisiones que recomiendo mantener

- no SPA al inicio
- no microservicios
- no app nativa
- no dependencias caras para busqueda
- no aprobacion automatica regulatoria
