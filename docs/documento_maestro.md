# Moodle Integration Hub (MIH)

## 1️⃣ Descripción general

**Moodle Integration Hub** es un plugin local para Moodle que centraliza integraciones con microservicios y sistemas externos.  
Su objetivo es ofrecer:

- Una **capa técnica reutilizable** para llamadas HTTP robustas (Gateway)
- Un **motor de eventos** configurable desde un dashboard (Event Bridge, Fase 2)
- **Observabilidad y control** de integraciones desde un panel amigable (Dashboard híbrido)

Evita que cada plugin tenga su propia lógica de conexión, manejo de tokens, retries y logging, simplificando la arquitectura de Moodle y mejorando la confiabilidad.

---

## 2️⃣ Problema que resolvemos

Plugins actuales que interactúan con microservicios:

- Configuran URLs y tokens en `settings.php` de manera aislada
- Implementan su propia lógica de HTTP, retry y manejo de errores
- No tienen observabilidad central
- No permiten reglas basadas en eventos sin tocar código

Esto genera:

- Código repetido y difícil de mantener
- Errores dispersos difíciles de depurar
- Integraciones poco robustas y no escalables
- Mala experiencia para administradores

**Integration Hub** centraliza todo esto en un único plugin local y configurable.

---

## 3️⃣ Filosofía

- **Open Source:** código de todos para todos
- **Viabilidad:** cada fase funciona por sí sola y es útil desde el primer día
- **Impacto:** ayuda a admins y desarrolladores a integrar Moodle con sistemas externos de manera profesional
- **Portafolio:** demuestra arquitectura desacoplada, event-driven y diseño limpio

---

## 4️⃣ Fases de desarrollo

### Fase 1 — Gateway de servicios (MVP)

**Objetivo:** crear infraestructura de llamadas HTTP centralizadas y dashboard híbrido.

- Registrar servicios (URL, token, método de autenticación)
- Realizar requests reutilizables desde cualquier plugin
- Manejar retries básicos
- Implementar circuito simple (CLOSED / OPEN)
- Loguear requests y errores
- **Dashboard híbrido:** página nativa + administración inline para crear/editar servicios

**Beneficio:** plugins llaman a microservicios sin duplicar lógica, admins gestionan servicios centralmente.

---

### Fase 2 — Event Bridge básico

**Objetivo:** permitir reaccionar a eventos de Moodle sin escribir código.

- Seleccionar eventos (usuario creado, curso completado, calificación, etc.)
- Asociar cada evento a un servicio y endpoint
- Plantillas de payload (`{{userid}}`, `{{courseid}}`)
- Queue asincrónica + cron
- Reutilizar Gateway para retries y logging

**Beneficio:** admins crean integraciones automáticas, plugins existentes se benefician sin cambios.

---

### Fase 3 — Observabilidad avanzada y dashboard

**Objetivo:** visibilidad y control completo de integraciones.

- Estado de circuitos (CLOSED / OPEN / Half-Open)
- Latencia promedio por servicio
- Últimos errores y requests fallidos
- Eventos pendientes en queue
- Reintentos manuales desde UI

**Beneficio:** admins y devs pueden depurar, medir confiabilidad y optimizar integraciones.  
**Dashboard híbrido** permite administración inline y vista centralizada de toda la información.

---

### Fase 2.5 — Soporte AMQP (RabbitMQ) [IMPLEMENTADO]

**Objetivo:** Extender el Gateway y Event Bridge para soportar mensajería asíncrona real.

- **Arquitectura de Transporte:**
  - `local_integrationhub\transport\contract`: Interfaz común.
  - `local_integrationhub\transport\http`: Driver para REST (existente refactorizado).
  - `local_integrationhub\transport\amqp`: Driver para RabbitMQ (nuevo).

- **Configuración:**
  - `type`: `rest` o `amqp`.
  - `base_url`: Para AMQP es la cadena de conexión (`amqp://user:pass@host:5672`).
  - `endpoint`: Para AMQP es la Routing Key.

---

## 5️⃣ Flujo de llamadas (Plugin → Gateway → Servicio)

1. Admin agrega servicio desde dashboard → guardado en DB
2. Plugin externo llama:

```php
$gateway = \local_integration_hub\gateway::instance();
$response = $gateway->request('judgeman', '/execute', $payload);
```

3. Gateway hace:

- Busca servicio en DB
- Aplica autenticación
- Verifica circuito
- OPEN → devuelve error inmediato
- CLOSED → hace request
- Retry policy en caso de fallo
- Logging en mdl_local_integrationhub_logs
- Devuelve respuesta al plugin

4. Microservicio recibe payload y responde
5. Gateway registra resultado y estado del circuito

## Escenario de fallo / retry

- Request falla → retries según configuración
- Si fallas consecutivas exceden límite → circuito OPEN
- Futuras llamadas bloqueadas hasta Half-Open → CLOSED

6️⃣ Dashboard híbrido — Mock visual

+-------------------------------------------------------------+
| Moodle Integration Hub                                      |
|-------------------------------------------------------------|
| [Agregar Servicio]  [Ver Logs]  [Actualizar Estado]        |
|-------------------------------------------------------------|
| Servicios Registrados                                       |
|-------------------------------------------------------------|
| Nombre       | URL Base                 | Auth   | Circuito | Último Error      |
|------------- |------------------------ |------- |----------|-----------------|
| Judgeman     | https://judgeman.local  | Bearer | CLOSED   | -                 |
| Sandbox      | https://sandbox.local   | APIKey | OPEN     | Timeout 2026-02-16|
+-------------------------------------------------------------+

+-------------------------------------------------------------+
| Formulario de Servicio                                      |
|-------------------------------------------------------------|
| Nombre del Servicio: [_________________________]            |
| URL Base:           [_________________________]            |
| Tipo de Auth:       ( ) Bearer  ( ) API Key                 |
| Token / Credencial: [_________________________]            |
| Timeout (seg):      [ 5 ]                                   |
| Retry Policy:       [Intentos: 3] [Backoff exponencial]    |
| Circuit Breaker:    [Fallos antes de OPEN: 5]              |
|                     [Tiempo para Half-Open: 30s]           |
|-------------------------------------------------------------|
| [Guardar]  [Cancelar]                                       |
+-------------------------------------------------------------+

7️⃣ UX / JS

- Form inline se despliega/oculta dinámicamente
- Validaciones básicas: URL válida, token no vacío, timeout positivo
- Feedback: “Servicio creado/actualizado exitosamente”

7️⃣ Roles y permisos (Fase 1)

local/integrationhub:manage → crear/editar/eliminar servicios (solo admins)

local/integrationhub:view → ver listado de servicios y logs

Plugins externos usan Gateway sin permisos especiales

8️⃣ Base de datos mínima (Fase 1)

mdl_local_integrationhub_services: id, name, base_url, auth_type, token, timeout, retry_policy_json, circuit_breaker_json, cache_ttl

mdl_local_integrationhub_circuits: service_id, failure_count, last_failure, state

mdl_local_integrationhub_logs: service, endpoint, status, latency, attempt_count, error_message, timestamp

(Fase 2 agregará reglas y queue)

9️⃣ Clases mínimas (Fase 1)

client.php → request principal

retry_policy.php → retry básico

circuit_breaker.php → manejar estado OPEN/CLOSED

service_registry.php → leer/escribir servicios desde DB

🔟 Próximos pasos antes de codificar

Revisar y aprobar flujo de Plugin → Gateway → Servicio

Mock visual del dashboard y form inline aprobado

Validaciones JS mínimas definidas

Confirmar tablas y clases mínimas

Una vez aprobado → escribir:

Clases PHP del Gateway

Dashboard híbrido (index.php + moodleform)

Plugin de prueba usando el Gateway (ej: The Judgeman)


---

Con este documento tienes **la guía completa**:

- Todo el proyecto definido desde Fase 1 a Fase 3  
- Cómo funciona cada flujo y escenario de fallo  
- Dashboard híbrido visual y UX  
- Roles y permisos claros  
- Tablas y clases mínimas  

Esto es **lo que necesitas para presentar a cualquier sistema o equipo**, y también sirve como **guía de implementación antes de tocar código**.  
