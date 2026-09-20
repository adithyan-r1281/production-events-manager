# Security

## Security principles

The plugin treats all public registration input as untrusted and keeps attendee data out of public responses.

## Input validation and sanitization

Registration names are sanitized as text and validated as non-empty values.

Email addresses are sanitized and validated with WordPress email APIs, then normalized before duplicate checks and persistence.

Event metadata is validated when saved in the admin and when relevant metadata is supplied through REST.

Capacity accepts a strict numeric value and is normalized to an integer.

## Authorization

The public registration endpoint is intentionally available without authentication. It does not expose existing registration data.

Admin registration management and CSV export require the `manage_event_registrations` capability.

Admin state-changing operations use WordPress nonces where applicable.

## SQL safety

Database operations use `$wpdb` APIs with prepared statements or typed `$wpdb->insert()` values.

Search values are escaped with `$wpdb->esc_like()` before being used in `LIKE` expressions.

The registration table uses a unique `(event_id, email)` constraint to enforce duplicate protection independently of application checks.

## CSRF

Administrative actions and exports are protected by capability checks and nonce validation.

The public registration endpoint is a public form submission endpoint rather than an authenticated state-changing admin action. Its business rules are enforced server-side by the registration service.

## XSS

User-controlled values are escaped at output boundaries in admin and frontend templates. Input sanitization is not treated as a substitute for output escaping.

## CSV injection

CSV export protects spreadsheet formula prefixes such as `=`, `+`, `-`, and `@` by prefixing affected values before writing them to CSV.

## Data exposure

The public registration API returns only a success message. It does not return attendee names, email addresses, registration IDs, counts, or registration listings.

Registration data is available only through the protected admin registrations interface/export.

## Database concurrency

Limited-capacity registrations use a transaction and row lock on the event post before capacity is checked. This prevents the common race where simultaneous requests each observe an available final slot.

## Uninstall behavior

Data deletion is opt-in. Normal deactivation and normal uninstall preserve plugin data.

Destructive uninstall requires the explicit `PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL` constant to be defined as `true`.

## Operational recommendations

- Use HTTPS in production.
- Keep WordPress, PHP, and the hosting database stack patched.
- Restrict administrator accounts and avoid granting `manage_event_registrations` unnecessarily.
- Back up the WordPress database before enabling destructive uninstall.
- Review CSV exports as sensitive attendee data and store them securely.
