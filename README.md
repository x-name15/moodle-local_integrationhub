# Integration Hub (Centro de Integración)

## Overview
**Integration Hub** is a local Moodle plugin designed to centralize and manage outgoing integrations with external microservices. It provides a dashboard for monitoring service health, viewing logs, and configuring integration rules that map Moodle events to external API calls.

## Key Features
- **Centralized Service Registry**: Manage all external services (metrics, analytics, gamification) in one place.
- **Event-Driven Architecture**: Capture **ANY** Moodle event (core or third-party) using a universal observer and dispatch it to configured services.
- **Transport Agnostic**: Supports **HTTP (REST)** and **AMQP (RabbitMQ)** protocols.
- **Resilience Patterns**: Built-in Circuit Breaker, exponential backoff retries, and task-based queueing for reliability.
- **Monitoring**: Real-time dashboard with success/failure charts and latency trends.

## Project Structure

```
local/integrationhub/
├── amd/                # JavaScript modules (Dashboard logic)
├── assets/             # External libraries (Chart.js)
├── classes/
│   ├── event/          # Event observers
│   ├── service/        # Core service logic (Registry, Circuit Breaker)
│   ├── task/           # Scheduled tasks (Queue processing)
│   ├── transport/      # Transport drivers (HTTP, AMQP)
│   └── gateway.php     # Main entry point for dispatching requests
├── db/                 # Database schema, events, upgrading, services
├── docs/               # Technical documentation
├── lang/               # Language strings (en, es)
├── test/               # CLI Test scripts
├── index.php           # Main Dashboard
├── logs.php            # Logs Viewer
├── queue.php           # Queue Monitor
└── settings.php        # Admin settings
```

## Recent Changes & Status

### 1. Architecture Refactor (AMQP Support)
- **Transport Pattern**: Introduced `classes/transport/contract.php` to decouple logic.
- **Drivers**:
    - `http.php`: Handles standard REST calls (Curl).
    - `amqp.php`: Handles RabbitMQ publishing (requires `php-amqplib`).
- **Gateway**: Updated to select drivers dynamically based on service `type`.

### 2. Universal Event Coverage
- **`db/events.php`**: Updated to listen to `\core\event\base`. This allows the plugin to interception ALL events in the system, enabling true centralized management without modifying other plugins.

### 3. Frontend & UI
- **Dashboard**: Fixes to layout (Bootstrap spacing) and Chart.js integration.
- **Charts**: Integration status and latency trends. Uses `Chart.js` (UMD) loaded from `assets/min`.
- **Translations**: Full support for English and Spanish (`es`).

### 4. Quality Assurance
- **Tests**: `test/test_charts.php` confirms data aggregation logic.
- **Linting**: AMD modules are compliant with ESLint.

## Installation / Setup
1.  **Code Placement**: Ensure the plugin is in `local/integrationhub`.
2.  **Dependencies**: Run `composer require php-amqplib/php-amqplib` in your Moodle root for AMQP support.
3.  **Install/Upgrade**: Run `php admin/cli/upgrade.php` to install DB tables.
4.  **Assets**: Ensure `assets/min/chart.umd.min.js` exists.

## Usage
1.  Go to **Site Administration > Server > Integration Hub**.
2.  Add a Service (HTTP or AMQP).
3.  Define **Rules** mapping Moodle events (e.g., `\core\event\user_created`) to your service.
4.  Monitor traffic via the **Dashboard** and **Queue Monitor**.
