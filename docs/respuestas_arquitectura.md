# Respuestas de Arquitectura – Moodle Integration Hub (MIH)

Este documento responde a las dudas críticas planteadas en `preguntas.md` y `preguntas2.md`, sirviendo como base para la implementación de microservicios compatibles.

## 1. Captura de Eventos (The Grand Observer)
- **Registro**: Se utiliza un único observador genérico para `\core\event\base` en `db/events.php`.
- **Alcance**: Captura eventos de Core y de cualquier plugin de terceros automáticamente.
- **Seguridad**: El observador solo realiza una consulta rápida a la tabla de reglas y, si hay coincidencia, encola una `adhoc_task`. **No hay I/O externo ni bloqueos** dentro del observador, protegiendo la transacción original de Moodle.

## 2. Persistencia y Cola (Adhoc Tasks & DLQ)
- **Mecanismo**: Se delega la encolación a las `adhoc_tasks` nativas de Moodle (`task_adhoc`).
- **DLQ**: Si una tarea falla 5 veces, MIH la mueve automáticamente a la tabla `local_integrationhub_dlq` (Dead Letter Queue) para inspección manual.
- **Campos Clave**: `status`, `attempts`, `faildelay` (en Moodle core) y `payload`, `error_message` (en nuestra tabla DLQ).

## 3. Worker y Reintentos
- **Ejecución**: Procesado por el cron de Moodle mediante `local_integrationhub\task\dispatch_event_task`.
- **Backoff**: Moodle aplica un backoff exponencial nativo a las tareas fallidas.
- **Circuit Breaker**: MIH añade una capa extra: si un servicio externo falla repetidamente, el Circuit Breaker (persistido en DB) se abre, evitando peticiones inútiles y protegiendo el sistema.

## 4. API Pública (Fachada MIH)
Para que otros plugins usen MIH manualmente sin depender de eventos:
```php
MIH::request('slug_del_servicio', '/endpoint', ['dato' => 'valor'], 'POST');
```
Esta API es la forma recomendada y estable de integración. Está diseñada para ser minimalistic y no requiere que el desarrollador externo conozca nada sobre colas, adaptadores o circuit breakers.

## 5. Arquitectura de Adaptadores (Transports)
- **Protocolos**: Soporte integrado para **REST (HTTP)** y **AMQP (RabbitMQ)**.
- **Desacoplamiento**: El `Gateway` usa la interfaz `transport_contract`, permitiendo añadir nuevos protocolos (gRPC, MQTT) sin tocar la lógica de negocio.
- **Circuit Breaker**: Cada servicio tiene su propio estado de Circuit Breaker persistido en DB (`local_integrationhub_cb`), protegiendo a Moodle de latencias externas.

## 6. Esquema de Eventos (Payload)
- **Sobre (Envelope)**: MIH permite definir un template JSON por regla.
- **Metadata**: Por defecto, MIH puede incluir `eventname`, `timestamp`, `userid`, `courseid`, etc., usando placeholders como `{{eventname}}`.
- **Idempotencia**: Se recomienda que el microservicio consumidor use el ID del evento de Moodle (si se incluye en el payload) para evitar duplicados.

## 7. Observabilidad y Escalabilidad
- **Dashboard**: Gráficos de éxito/fallo y latencia media.
- **Monitor**: Interfaz para ver tareas pendientes y la DLQ.
- **Logs**: Registro detallado de cada petición en `local_integrationhub_log` (status HTTP, latencia, intentos).
- **Multi-Nodo**: Al usar la infraestructura de `adhoc_tasks` de Moodle, MIH es **seguro en entornos clusterizados** (Moodle gestiona el bloqueo para que dos nodos no procesen la misma tarea).

---

### Guía para el Microservicio de Prueba
Para probar MIH, tu microservicio debería:
1.  **Escuchar HTTP POST**: En el endpoint configurado en la Regla.
2.  **Validar Auth**: MIH soporta Bearer Token o API Key.
3.  **Payload**: Esperar un JSON (configurable en la UI de MIH).
4.  **Responder**: 200 OK para confirmar entrega. Cualquier error 4xx/5xx activará la lógica de reintento y, eventualmente, el DLQ.
