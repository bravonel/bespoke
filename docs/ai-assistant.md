# Asistente IA de Bespoke OS

El asistente IA queda integrado como un módulo de consulta operativa. En esta primera fase no ejecuta cambios: responde preguntas con datos reales de Bespoke OS, muestra fuentes navegables y guarda auditoría de cada solicitud.

## Variables de entorno

```dotenv
OPENAI_API_KEY=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-5.6
OPENAI_TTS_MODEL=gpt-4o-mini-tts
OPENAI_TTS_VOICE=coral
```

En local la llave puede vivir en `.env.local`; el sistema la lee como fallback si no existe `OPENAI_API_KEY` en el entorno normal. En producción debe configurarse en el `.env` del release activo o del entorno que use cPanel.

## Flujo

1. El panel global envía la pregunta a `POST /ai/assistant`.
2. `AiContextBuilder` prepara un contexto limitado: resumen general, proyectos relevantes, tareas relevantes y carga del día.
3. `AiAssistant` registra auditoría en `ai_assistant_messages`.
4. `OpenAiProvider` llama al endpoint `responses` de OpenAI.
5. La respuesta vuelve al panel con fuentes internas como dashboard, proyectos y tareas.
6. Si el usuario activa audio, `POST /ai/assistant/speech` genera un MP3 con voz de OpenAI desde el backend.

## Voz

- El botón **Micrófono** usa dictado del navegador para llenar el mismo campo de texto.
- El textarea se mantiene para escribir manualmente.
- El botón **Escuchar** y el modo **Audio activo** usan OpenAI Text to Speech desde el servidor.
- La API key nunca se envía al navegador.

## Límites intencionales de la fase 1

- No crea, edita ni elimina datos.
- No manda toda la base de datos al modelo.
- No expone llaves ni secretos al navegador.
- Si falta contexto, el asistente debe decirlo en vez de inventar.

## Siguientes fases sugeridas

1. Dictado para crear briefs, tareas o notas.
2. Ejecución de acciones con confirmación explícita.
3. Alertas proactivas por vencimientos, bloqueos, falta de horas o sobrecarga.
4. Permisos por rol para decidir qué acciones puede sugerir o ejecutar cada usuario.
