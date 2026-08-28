---
name: Remediación transversal de seguridad para proyectos con IA
description: Requisitos de compatibilidad y seguridad para cerrar los hallazgos del informe del 18 de agosto de 2026.
targets:
  - ../security_best_practices_report.md
  - ../../Proyectos/rag-multi-api/app/**/*.py
  - ../../Proyectos/rag-multi-api/tests/**/*.py
  - ../../Proyectos/cofepris-agent/app/**/*.py
  - ../../Proyectos/cofepris-agent/tests/**/*.py
  - ../../Proyectos/venues/bot/**/*.py
  - ../../Proyectos/venues/tests/**/*.py
  - ../../Proyectos/glaucocare/lib/services/**/*.dart
  - ../../Proyectos/glaucocare/apps/cms_laravel/app/**/*.php
  - ../../Proyectos/glaucocare/test/**/*.dart
  - ../../Proyectos/pharmapro/app/Services/Ai/**/*.php
  - ../../Proyectos/pharmapro/app/Livewire/Demo/Exam.php
  - ../../Proyectos/pharmapro/tests/**/*.php
  - ../app/Services/AI/**/*.php
  - ../tests/Feature/AiAssistantSecurityTest.php
  - ../../Proyectos/tereginteractive.com/public/chatgpt.js
  - ../../Proyectos/tereginteractive.com/public/js/avatar/chatgpt.js
---

# Remediación transversal de seguridad para proyectos con IA

La remediación debe preservar los contratos funcionales existentes y cerrar primero los riesgos que permiten exponer credenciales, cruzar tenants, consultar sesiones ajenas, consumir proveedores sin control o convertir contenido no confiable en instrucciones.

## Reglas comunes

- Todo texto de usuarios, PDFs, scraping, RAG, base de datos, voz o enlaces se considera dato no confiable.
- Los prompts deben separar instrucciones de evidencia, declarar que la evidencia no puede cambiar políticas ni solicitar herramientas y conservar procedencia cuando exista.
- Las salidas del modelo deben validarse por esquema, enum, rango y longitud antes de afectar estado o enviarse a otro canal.
- La autorización y las decisiones sensibles deben ser determinísticas e independientes del modelo.
- Los endpoints costosos deben tener autenticación o una sesión firmada, límites de tamaño y rate limiting.
- Ninguna prueba, log, reporte o commit puede incluir valores de credenciales.
- Los cambios deben ser compatibles por defecto; cualquier endurecimiento que requiera configuración debe fallar cerrado en producción y permitir un modo local explícito.

## `rag-multi-api`

- La autenticación no debe registrar la API key, salt ni hashes.
- Tenant y topic se derivan de la credencial autenticada; una petición no puede reemplazar el topic autorizado.
- Toda búsqueda vectorial usa namespace/filtro del tenant y nunca reintenta sin aislamiento.
- `top_k` y modelos se validan contra rangos/allowlists del servidor.
- El endpoint de jobs requiere la misma identidad y sólo expone trabajos del tenant solicitante.
- La evidencia RAG se etiqueta como no confiable y no puede modificar instrucciones.
  `[@test] ../../Proyectos/rag-multi-api/tests/test_security.py`

## `cofepris-agent`

- Todo `/api/*`, excepto catálogos explícitamente públicos, requiere `Authorization: Bearer` mediante una dependencia central.
- Las credenciales se configuran en variables de entorno y se comparan en tiempo constante; no se aceptan por URL.
- Clientes, productos, claims, revisiones y validaciones quedan asociados a la identidad/tenant y se filtran en servidor.
- Revisión, leyendas y exportación tienen rate limit, límites de texto, archivos y tamaño total.
- Errores públicos no exponen excepciones internas.
- Copy, PDFs, OCR, campos almacenados y contexto RAG se tratan como evidencia no confiable.
  `[@test] ../../Proyectos/cofepris-agent/tests/test_security.py`

## `venues`

- Se conserva el payload existente de `POST /chat`; se permiten campos adicionales compatibles.
- El servidor emite sesiones de alta entropía y una prueba de posesión firmada. Una sesión existente no puede abrirse o actualizarse sin esa prueba.
- La UI conserva la prueba de posesión únicamente durante la sesión de navegador, no como credencial de cuenta.
- `/chat` aplica límites de mensaje, rate limit y presupuesto para live fallback.
- Datos de venues y resultados externos son evidencia no confiable; nunca instrucciones.
- Los errores públicos son genéricos.
  `[@test] ../../Proyectos/venues/tests/test_chat_security.py`

## `glaucocare`

- Los flujos directos cloud de NLU y TTS permanecen deshabilitados en Flutter hasta que exista aprobación explícita de privacidad y un proxy CMS autenticado.
- Crear el proxy no está autorizado por esta remediación porque transmitiría datos potencialmente médicos a un tercero; requiere revisión separada de privacidad, consentimiento, retención y proveedor.
- Ninguna clave sensible se compila dentro de Flutter.
- Los logs de release no contienen nombre de medicamento, dosis, horarios, especialidad ni texto de voz.
- Medicación y horarios requieren confirmación explícita antes de persistirse.
- Se preservan el modo local, fallbacks y contratos actuales de onboarding.
  `[@test] ../../Proyectos/glaucocare/test/services/openai_nlu_service_test.dart`

## `pharmapro`

- La salida de IA se valida mediante un contrato estricto antes de entrar a `Exam`.
- `attention_delta`, score y métricas tienen rangos definidos; acciones usan enums cerrados.
- El modelo no puede terminar un examen, otorgar certificación ni sustituir la evaluación determinística.
- `ConversationEvaluator` y `DemoExamState` siguen siendo autoridades del dominio.
- Prompts etiquetan transcript, objeciones y contenido de curso como evidencia no confiable.
  `[@test] ../../Proyectos/pharmapro/tests/Unit/ExamAiOrchestratorSecurityTest.php`
  `[@test] ../../Proyectos/pharmapro/tests/Feature/DemoRoleplaySemanticDisciplineTest.php`

## `bespoke`

- Los valores del contexto JSON se declaran contenido no confiable y no pueden cambiar políticas, permisos ni solicitar acciones.
- La salida se limita y valida antes de enviarse por WhatsApp.
- La fase actual permanece de sólo lectura; no se agregan herramientas de mutación.
- Cualquier futura fase de acciones requiere allowlist, autorización independiente, confirmación, idempotencia y auditoría.
  `[@test] ../tests/Feature/AiAssistantSecurityTest.php`

## `tereginteractive.com`

- El navegador no llama directamente a OpenAI ni contiene un lugar para una clave secreta.
- Si no existe backend autenticado, el prototipo falla cerrado con un mensaje controlado.
- Ningún cambio debe alterar otras funciones del sitio/avatar.
  `[@test] ../../Proyectos/tereginteractive.com/tests/FrontendAiSecurityTest.mjs`

## Validación y cierre

- Ejecutar pruebas enfocadas y las suites existentes relevantes en cada repositorio.
- Revisar los diffs para no incluir cambios previos del usuario.
- Actualizar el informe con estado por hallazgo: corregido, mitigado o acción manual pendiente.
- Acciones manuales como rotar credenciales, configurar secretos, restricciones de claves cliente o reglas de infraestructura deben quedar enumeradas sin exponer valores.
