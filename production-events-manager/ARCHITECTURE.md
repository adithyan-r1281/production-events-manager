# Architecture

## Overview

Production Events Manager follows a small service/repository architecture around WordPress's native post, taxonomy, metadata, REST, and admin APIs.

The plugin separates event presentation and lifecycle rules from registration persistence and public registration handling.

## Directory structure

```text
production-events-manager/
├── production-events-manager.php
├── uninstall.php
├── includes/
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-event-post-type.php
│   ├── class-event-meta.php
│   ├── class-event-lifecycle.php
│   ├── class-event-repository.php
│   ├── class-event-frontend.php
│   ├── class-registration-repository.php
│   ├── class-registration-service.php
│   ├── class-rest-controller.php
│   ├── class-admin.php
│   ├── class-csv-exporter.php
│   └── class-capabilities.php
├── assets/
│   ├── css/event-registration.css
│   └── js/
├── templates/
│   ├── archive-event.php
│   ├── single-event.php
│   └── registration-form.php
└── project documentation
```

The current implementation does not contain a separate `class-plugin.php`; the root plugin file performs the bootstrap and dependency wiring.

## Bootstrap

`production-events-manager.php`:

1. Blocks direct access.
2. Loads plugin classes.
3. Registers activation/deactivation hooks.
4. Registers the event post type and venue taxonomy.
5. Schedules one-time rewrite flushing after activation.
6. Registers event metadata and frontend rendering.
7. Constructs event repositories/services.
8. Constructs the registration service and REST controller.
9. Registers the admin registration screen and CSV exporter.

Dependencies are instantiated explicitly and passed into services that need them.

## Event model

Events use the `pem_event` custom post type.

WordPress post content stores the title/description. Event-specific metadata stores:

- `pem_start_datetime`
- `pem_end_datetime`
- `pem_capacity`
- `pem_registration_closing_datetime`
- `pem_cancelled`

Venues use the `pem_venue` taxonomy.

## Event metadata validation

`Production_Events_Event_Meta` owns event metadata registration, admin UI, save-time validation, REST metadata validation, and capacity sanitization.

Date values are parsed using the WordPress site timezone. Invalid values are rejected rather than silently persisted.

The registration closing datetime must not be later than the event start.

## Event lifecycle

`Production_Events_Event_Lifecycle` is the centralized business-logic layer for determining event timing and lifecycle status.

Consumers should use this service instead of duplicating time comparisons throughout templates, REST code, or admin code.

## Event repository

`Production_Events_Event_Repository` provides event queries and event-related data access, including:

- published events
- upcoming events
- venue-filtered events
- event capacity
- venues
- publication state

The archive intentionally focuses on upcoming published events.

## Registration flow

```text
Browser
  │
  ▼
REST controller
  │ validates request shape
  ▼
Registration service
  │ validates event/lifecycle/input/duplicate rules
  ▼
Registration repository
  │ persists registration
  │ enforces database uniqueness
  │ atomically checks limited capacity
  ▼
Registrations table
```

The service owns business rules. The repository owns database persistence and concurrency-sensitive operations.

## Capacity concurrency

For limited-capacity events, the repository starts a transaction and locks the corresponding event post row with `FOR UPDATE` before checking the registration count and inserting the new registration.

This serializes capacity checks for the same event and prevents concurrent requests from both observing the same final available slot.

The `(event_id, email)` unique key remains the final database-level duplicate safeguard.

## REST API

The REST controller exposes only the public registration operation. It does not expose registration listings, registration IDs, or other attendee records.

The endpoint is public by design. Registration authorization is therefore based on event state and registration rules rather than user authentication.

## Admin

`Production_Events_Admin` owns the registrations admin screen.

`Production_Events_CSV_Exporter` handles CSV export independently so export processing does not become part of the page-rendering method.

`Production_Events_Capabilities` owns the plugin-specific `manage_event_registrations` capability.

## Frontend templates

`Production_Events_Event_Frontend` integrates plugin templates with WordPress's template resolution.

The templates provide fallback event archive/single rendering without requiring a page builder or theme-specific component.

Registration assets are enqueued only on singular event pages.

## Lifecycle and data retention

Activation creates/updates the registration table and grants the plugin capability to administrators.

Deactivation flushes rewrite rules but does not delete data.

Uninstall preserves data unless `PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL` is explicitly set to `true`.
