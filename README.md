# Events Manager Custom

Events Manager Custom is a custom WordPress plugin for creating events, publishing an upcoming-events archive, displaying event lifecycle information, accepting public registrations, and managing registrations from the WordPress admin.

## Features

- `pem_event` custom post type for events.
- `pem_venue` taxonomy for venues.
- Event start/end datetime, registration closing datetime, capacity, cancellation state, featured image, title, and description.
- Timezone-aware lifecycle handling using the WordPress site timezone.
- Upcoming events archive with pagination and a frontend venue/date filter (`?pem_venue_id=`, `?pem_date_from=`, `?pem_date_to=`).
- Single-event page with lifecycle and registration availability information.
- Public REST registration endpoint.
- Duplicate-registration protection using normalized email addresses and a database unique constraint.
- Atomic capacity enforcement for limited-capacity events.
- Admin registration management with filtering, search, pagination, and CSV export.
- Dedicated `manage_event_registrations` capability.
- Responsive and accessible registration UI.
- Opt-in destructive uninstall behavior; data is preserved by default.

## Requirements

The plugin is designed for a standard WordPress installation with the WordPress REST API and a MySQL-compatible database supported by WordPress.

The plugin does not currently declare a minimum WordPress or PHP version in its plugin header. Confirm the target site's WordPress/PHP versions against the APIs used by the plugin before production deployment.

Atomic capacity enforcement relies on transactional database behavior and row locking. The registration table should therefore use a transactional storage engine; `dbDelta()` creates the table using the site's normal WordPress database charset/collation configuration.

## Installation

1. Copy the `production-events-manager` directory into `wp-content/plugins/`.
2. Activate **Events Manager Custom** from **Plugins → Installed Plugins**.
3. Go to **Events** and create an event.
4. Add a venue, event dates, capacity, and optional registration closing time.
5. Publish the event.
6. Visit `/events/` to verify the archive.
7. Open an event and submit a test registration.
8. Review registrations under **Events → Registrations**.

If the plugin has previously been active, flush rewrite rules only through normal WordPress/plugin activation/deactivation flows rather than modifying the database manually.

## Event lifecycle

The lifecycle service derives the following statuses:

- `cancelled` — the event-specific cancellation flag is enabled.
- `expired` — the event end datetime has passed.
- `registration_closed` — the registration closing datetime has passed.
- `started` — the event start datetime has passed, but it has not ended and registration has not already closed.
- `upcoming` — none of the conditions above apply.

The lifecycle service evaluates cancellation first, then expiry, registration closing, and start state.

Public registration is allowed only when the event is published, not cancelled, not started/expired, registration is open, and capacity remains available.

## Timezone handling

Event datetimes are interpreted in the WordPress site timezone obtained through `wp_timezone()`.

The plugin does not rely on the server's timezone for event business logic. Registration timestamps use WordPress's `current_time( 'mysql' )` behavior.

## Registration API

### Endpoint

`POST /wp-json/production-events/v1/events/{event_id}/registrations`

### Request body

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com"
}
```

### Success

HTTP `201`:

```json
{
  "success": true,
  "message": "Registration successful."
}
```

The public response does not expose the registration ID or other registration records.

### Relevant error responses

- `400` — invalid request data or event metadata/lifecycle validation failure.
- `403` — registration is not currently allowed, including capacity/lifecycle restrictions.
- `404` — event does not exist or is not a valid event for registration.
- `409` — the submitted email is already registered for the event.
- `500` — registration persistence failure.

The endpoint is intentionally public because visitors must be able to register without being logged in. Server-side validation and the registration service enforce event state, duplicate protection, capacity, sanitization, and persistence rules.

## Database

Registrations are stored in:

`{wpdb->prefix}production_event_registrations`

Columns:

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint unsigned | Registration primary key |
| `event_id` | bigint unsigned | Associated event post ID |
| `name` | varchar(255) | Registrant name |
| `email` | varchar(320) | Normalized registrant email |
| `registered_at` | datetime | WordPress-timezone registration timestamp |

Indexes include `event_id`, `email`, and `registered_at`. A unique key on `(event_id, email)` prevents duplicate registrations at the database level.

## Admin registrations

Users with the `manage_event_registrations` capability can access **Events → Registrations**.

The admin page supports:

- Event filtering.
- Name/email search.
- Pagination.
- CSV export.

Registration records and email addresses are not exposed through the public REST API.

## Data retention and uninstall

Deactivation does not delete events, metadata, venues, registrations, or plugin data.

Uninstall is also non-destructive by default.

To explicitly enable destructive uninstall, define this constant as `true` before the plugin is uninstalled:

```php
define( 'PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL', true );
```

When enabled, uninstall removes plugin event posts, venue terms, the registrations table, the plugin rewrite-flush option, and the plugin-specific administrator capability.

Use this setting only when permanent deletion is intentional.

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for responsibilities and data flow.

## Security

See [SECURITY.md](SECURITY.md) for the security model and defensive controls.

## Performance

See [PERFORMANCE.md](PERFORMANCE.md) for query, indexing, asset, and concurrency considerations.

## Testing

See [TESTING.md](TESTING.md) for the manual regression checklist and deferred automated-test setup.

## Demo checklist

See [DEMO.md](DEMO.md) for a repeatable demonstration checklist.
