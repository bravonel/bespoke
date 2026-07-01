# PRD MVP

## 1. Resumen ejecutivo

`Bespoke OS` sera una plataforma interna y compartible con clientes para ordenar la operacion diaria de Bespoke Advertising y reducir errores previos al envio de materiales a COFEPRIS.

El MVP incluye dos modulos:

1. `Operacion de proyectos`
2. `Revision de claims y materiales`

La propuesta prioriza velocidad de adopcion, bajo costo operativo y trazabilidad.

## 2. Problema

Hoy la agencia depende de seguimiento distribuido entre mensajes, archivos, correos y memoria de las personas. Eso genera:

- poca claridad de estatus real por proyecto
- seguimiento manual costoso para cuentas y direccion
- retrasos por falta de responsables claros
- baja visibilidad para cliente y equipo
- revisiones tardias de claims, referencias y redaccion
- riesgo de enviar materiales con errores a revision regulatoria

## 3. Vision del producto

Crear una herramienta que funcione como un `capataz digital`:

- diga que sigue
- recuerde pendientes
- concentre contexto por proyecto
- automatice revisiones repetitivas
- deje evidencia de quien hizo que y cuando

## 4. Objetivos del negocio

- reducir tiempo de seguimiento operativo
- aumentar puntualidad de entregas
- bajar retrabajo antes de COFEPRIS
- facilitar colaboracion entre cuentas, medico, diseno y cliente
- construir una base modular para futuros procesos de la agencia

## 5. Principios del producto

- `simple antes que completo`
- `movil primero`
- `menos captura manual`
- `automatizacion asistida, no automatizacion ciega`
- `todo proyecto debe tener contexto y siguiente paso`
- `toda revision regulatoria debe dejar evidencia`

## 6. Usuarios y roles

### Roles internos

- `Admin`: configura catalogos, permisos, plantillas y clientes
- `Direccion`: ve carga, riesgos, prioridad y avance global
- `Cuentas`: crea proyectos, coordina responsables, da seguimiento
- `Medico`: sube estudios, define claims y referencias aprobadas
- `Diseno`: sube materiales y atiende observaciones
- `Trafico / PM`: mantiene tablero, fechas, bloqueos y capacidad
- `Legal / Regulatorio`: revisa hallazgos y decide si el material esta listo

### Roles externos

- `Cliente`: revisa materiales, comentarios y aprobaciones segun permisos

## 7. Alcance del MVP

### Modulo 1: Operacion de proyectos

#### Objetivo

Dar a cada persona una vista clara de:

- que tiene pendiente hoy
- quien esta bloqueando que
- en que etapa va cada proyecto
- que aprobaciones faltan

#### Funciones incluidas

- alta de clientes, marcas y proyectos
- plantillas de proyecto por tipo de trabajo
- tablero kanban por etapas
- lista personal de pendientes
- responsables y fechas compromiso
- comentarios por tarea y entregable
- archivos por proyecto y entregable
- aprobaciones internas y del cliente
- recordatorios automaticos por correo y WhatsApp
- resumen diario y semanal
- vista cliente de solo lectura o aprobacion

#### No incluye en V1

- control financiero avanzado
- facturacion
- tiempos detallados tipo timesheet
- planeacion compleja de recursos
- app movil nativa

### Modulo 2: Revision de claims y materiales

#### Objetivo

Reducir errores antes de enviar materiales a COFEPRIS mediante una revision asistida por OCR + reglas + IA.

#### Funciones incluidas

- carga de estudios cientificos en PDF
- carga de claims aprobados por el area medica
- carga de referencias y citas esperadas
- carga del material final en PDF
- extraccion de texto de estudios y material
- deteccion de claims presentes o ausentes
- validacion de referencias esperadas vs material
- revision de ortografia y redaccion
- hallazgos con semaforo verde, amarillo y rojo
- reporte auditable por material
- bitacora de revisiones y versionado

#### No incluye en V1

- aprobacion regulatoria automatica
- envio directo a COFEPRIS
- deteccion perfecta de todo contenido visual complejo
- entrenamiento de modelo propietario

## 8. Flujos principales

### Flujo A: Proyecto

1. Cuentas crea proyecto desde plantilla
2. Se asignan responsables y fechas
3. Cada area trabaja sobre tareas y entregables
4. El sistema envia recordatorios y muestra bloqueos
5. Se realiza aprobacion interna
6. Cliente revisa o aprueba
7. Proyecto cambia de etapa o se cierra

### Flujo B: Material con claims

1. Medico sube estudios y define claims aprobados
2. Diseno sube PDF del material
3. El sistema lanza revision automatica
4. Se genera semaforo y lista de hallazgos
5. Medico o regulatorio valida hallazgos
6. Diseno corrige
7. El sistema corre nueva revision y guarda historial
8. Cuando no haya hallazgos criticos, se marca listo para envio

## 9. Requisitos funcionales

### Generales

- autenticacion y control de acceso por rol
- historial de actividad por proyecto
- archivos centralizados por cliente, proyecto y material
- notificaciones configurables
- buscador global
- filtros por cliente, marca, responsable y estado

### Proyectos

- crear proyecto desde plantilla
- mover tareas entre etapas
- definir dependencias simples y bloqueos
- marcar tareas como listas
- registrar aprobaciones y rechazos
- generar resumen de estado

### Revision de claims

- asociar estudios con una marca o proyecto
- extraer texto de PDFs
- mapear claims esperados
- detectar claims exactos o semanticamente parecidos
- validar existencia de citas y referencias
- marcar hallazgos por severidad
- exportar reporte

## 10. Requisitos no funcionales

- interfaz usable en movil desde navegador
- tiempo de carga razonable en vistas operativas
- trazabilidad completa de cambios
- procesamiento asincrono para revisiones pesadas
- permisos por rol y por cliente
- almacenamiento seguro de documentos

## 11. Metricas de exito

### Operacion

- porcentaje de proyectos con siguiente paso definido
- tareas vencidas por semana
- tiempo promedio para respuesta de aprobacion
- porcentaje de proyectos actualizados en las ultimas 24 horas

### Revision

- numero de hallazgos detectados antes del envio
- ciclos promedio de correccion por material
- tiempo promedio de revision previa
- porcentaje de materiales que salen sin hallazgos criticos

## 12. Riesgos

- resistencia del equipo si el sistema obliga demasiada captura
- baja calidad de OCR en algunos PDFs
- claims redactados de forma muy distinta al respaldo cientifico
- expectativa incorrecta de que la IA sustituye aprobacion humana
- exceso de alcance antes de consolidar el MVP

## 13. Supuestos

- el equipo puede trabajar primero en navegador movil y desktop
- los materiales finales se compartiran principalmente en PDF
- los estudios y referencias podran centralizarse por marca o proyecto
- la aprobacion final seguira siendo humana

## 14. Criterios de salida del MVP

El MVP se considera listo cuando:

1. un proyecto puede crearse, operarse y aprobarse dentro del sistema
2. cada usuario puede ver su trabajo del dia sin buscarlo manualmente
3. un material PDF puede revisarse contra claims y referencias cargadas
4. el sistema produce un reporte de hallazgos claro y reutilizable
5. cliente interno o externo puede revisar sin depender de cadenas de correo
