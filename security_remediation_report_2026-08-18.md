# Reporte de remediación transversal de seguridad de IA

Fecha de cierre técnico: 18 de agosto de 2026  
Alcance: siete aplicaciones con IA identificadas en la auditoría transversal  
Especificación: `specs/ai-security-remediation.spec.md`

## Resultado ejecutivo

Se implementaron controles en los siete proyectos priorizados. Los dos hallazgos críticos y los riesgos técnicos P1/P2 quedaron corregidos o mitigados en código. Se ejecutaron 95 pruebas enfocadas, todas aprobadas. No se hicieron commits, pushes, despliegues, rotaciones de credenciales ni cambios de infraestructura.

Quedan cinco acciones operativas que requieren decisión o acceso del propietario: configurar credenciales nuevas de COFEPRIS, investigar/rotar secretos si hubo exposición de logs o copias, aprobar cualquier transmisión cloud de datos médicos de GlaucoCare, restringir claves cliente de Google y planear la actualización de dependencias de TeregInteractive.

## Estado por hallazgo

| Hallazgo | Estado | Resultado |
|---|---|---|
| SEC-001 | Corregido en código; rotación condicional pendiente | `rag-multi-api` dejó de registrar API keys, salts y hashes. Comparación en tiempo constante. |
| SEC-002 | Corregido en código; configuración requerida | `cofepris-agent` exige Bearer central, separa datos por tenant y falla cerrado si no hay claves configuradas. |
| SEC-003 | Corregido | Namespace/filtros obligatorios, sin fallback abierto; topic/modelos/top-k se validan en servidor. |
| SEC-004 | Corregido en los flujos revisados | RAG, PDFs, campos, historial y transcripciones se etiquetan como evidencia no confiable; se reforzaron prompts y pruebas. |
| SEC-005 | Corregido | `venues` usa prueba HMAC de posesión de sesión, rate limit, límites y presupuesto de fallback. |
| SEC-006 | Mitigado de forma segura | GlaucoCare eliminó claves y llamadas directas de NLU/TTS del cliente, redactó logs y mantiene fallbacks locales. Cloud queda deshabilitado hasta revisión de privacidad y proxy aprobado. |
| SEC-007 | Acción manual pendiente | El `.env` local de `cofepris-agent` no fue leído, copiado ni modificado; debe migrarse a un gestor de secretos y rotarse si la carpeta fue compartida. |
| SEC-008 | Corregido | PharmaPro normaliza esquema, enums, rangos y longitudes. La IA no puede finalizar el examen; esa decisión queda en reglas determinísticas. |
| SEC-009 | Corregido para la fase actual | Bespoke trata su JSON como no confiable y limita/limpia la respuesta antes de WhatsApp. Continúa sin herramientas de mutación. |
| SEC-010 | Corregido | TeregInteractive retiró ambas llamadas OpenAI del navegador y falla cerrado con un mensaje controlado. |
| SEC-011 | Corregido en los proyectos señalados | Se agregaron límites de payload/archivos/texto, rate limiting, allowlists y errores públicos genéricos. |
| SEC-012 | Acción manual pendiente | Deben verificarse restricciones por referrer/bundle/API, cuotas y alertas de las claves cliente de Google inventariadas. |

## Cambios por proyecto

### `rag-multi-api`

- Eliminación de logging sensible y autenticación con comparación en tiempo constante.
- Token HMAC de acceso a jobs ligado a tenant/job; resultados aislados por tenant.
- Validación central de pregunta, topic, modelos y top-k.
- Namespace/filtro obligatorio en Pinecone y eliminación del reintento sin aislamiento.
- Contexto y pregunta delimitados como no confiables; citas se limpian de forma determinística.
- CORS y configuración segura por defecto; corrección del contrato del worker Celery.

Validación: 4 pruebas de seguridad aprobadas; `compileall` y `git diff --check` aprobados.

### `cofepris-agent`

- Autenticación Bearer central con tokens por tenant, longitud mínima y comparación constante.
- Separación tenant-aware de clientes, productos, claims, revisiones y validaciones.
- Rate limit de rutas costosas; límites de texto, número de archivos y tamaño total.
- Errores públicos genéricos y validación de relaciones entre entidades.
- Evidencia externa/RAG/PDF fuera del mensaje de sistema y declarada no confiable.
- Frontend con token sólo en memoria, escape de salida del modelo y validación de URLs.

Validación: 4 pruebas de seguridad y compilación Python aprobadas. La carpeta no usa Git; se creó respaldo recuperable en `/private/tmp/cofepris-agent-security-backup-20260818`.

### `venues`

- Sesiones firmadas con prueba de posesión ligada al UUID.
- Prueba conservada sólo en `sessionStorage`; compatibilidad del identificador existente.
- Rate limit por cliente, CORS configurable y límite de mensaje.
- Presupuesto por sesión para búsquedas externas, además de cooldown y límites existentes.
- Historial, RAG y datos de venues marcados como no confiables; errores genéricos.

Validación: 3 pruebas aprobadas; compilación Python y `git diff --check` aprobados.

### `glaucocare`

- Eliminadas las claves compilables y llamadas directas a Gemini/Google Cloud TTS.
- NLU y TTS cloud fallan cerrado; se conservan análisis local y TTS del sistema.
- Logs de medicamento, dosis, especialidad y voz sustituidos por longitud redactada.
- No se creó un proxy nuevo: transmitir contenido médico a un tercero exige aprobación explícita de privacidad.

Validación: 55 pruebas aprobadas; `dart format`, `flutter analyze` y `git diff --check` aprobados.

### `pharmapro`

- Prompts distinguen instrucciones de transcripciones, historial y contenido de curso no confiables.
- Normalización estricta de score, attention delta, enums, listas y longitudes.
- Una solicitud `action=end` del modelo se convierte en `interrupt`; sólo el dominio puede terminar el examen.
- Fragmentos RAG limitados y saneados antes del prompt.

Validación: 16 pruebas y 69 aserciones aprobadas; Pint, lint PHP y `git diff --check` aprobados.

### `bespoke`

- Política explícita contra instrucciones incrustadas en pregunta, conversación y contexto JSON.
- Delimitación de datos no confiables y prohibición de revelar credenciales o generar contenido activo.
- Salida limpiada y limitada a 3,500 caracteres en WhatsApp y 12,000 en web.
- Se mantiene la fase de sólo lectura, sin nuevas acciones ni herramientas.

Validación: 12 pruebas y 41 aserciones aprobadas; Pint, lint PHP y `git diff --check` aprobados.

### `tereginteractive.com`

- Retiradas las llamadas, placeholders de clave y encabezados Bearer de los dos módulos públicos.
- Ambos contratos devuelven un mensaje controlado sin red.
- Prueba verifica que `fetch` no se ejecuta y que no reaparezcan endpoint, clave o autorización.

Validación: 1 prueba Node aprobada; sintaxis, build Vite de producción y `git diff --check` aprobados. Las dependencias se instalaron localmente sin crear lockfile. NPM reportó 3 vulnerabilidades preexistentes (1 baja, 1 moderada y 1 alta); no se aplicó `npm audit fix --force` porque puede introducir cambios incompatibles.

## Acciones manuales obligatorias

1. Configurar `COFEPRIS_API_KEYS_JSON` con tokens nuevos de al menos 24 caracteres antes de iniciar o publicar `cofepris-agent`. Actualizar el frontend con un token autorizado sólo durante la sesión.
2. Determinar si los logs históricos de `rag-multi-api` salieron del equipo o tuvieron terceros con acceso. Si ocurrió o no puede descartarse, rotar API keys, salts/hashes derivados y purgar logs conforme a la política de retención.
3. Mover el secreto actual de `cofepris-agent/.env` a un gestor de secretos. Rotarlo si la carpeta se copió, comprimió, respaldó o compartió.
4. Para GlaucoCare, realizar evaluación de privacidad y proveedor antes de habilitar cloud: consentimiento, finalidad, minimización, región, retención, contrato y autenticación del proxy. Mientras tanto, mantener cloud deshabilitado.
5. Revisar las claves cliente de Google del inventario SEC-012: restricciones por HTTP referrer o bundle ID, APIs permitidas, cuotas y alertas; rotar las que estén abiertas.
6. Planear una actualización compatible de dependencias de TeregInteractive. Crear y revisar un lockfile en un cambio separado, identificar los avisos exactos y evitar `npm audit fix --force` sin pruebas de regresión.

## Integridad del trabajo

- Se preservaron los cambios preexistentes y no relacionados en `venues`, `glaucocare`, `pharmapro` y `bespoke`.
- No se revirtieron eliminaciones ni archivos sin seguimiento del usuario.
- No se imprimieron valores de credenciales en pruebas, comandos o reportes.
- No se hizo `git add`, commit, push, despliegue ni modificación de servicios externos.

## Limitaciones

La remediación cubre código y pruebas locales enfocadas. No sustituye pentest, análisis de CVEs/dependencias, revisión de configuración cloud, observación de tráfico real ni validación de despliegues. PHP/Laravel y Dart/Flutter se revisaron manualmente; Python/FastAPI y JavaScript siguieron las guías de seguridad aplicables.
