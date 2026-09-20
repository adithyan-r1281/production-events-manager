=== Production Events Manager ===
Contributors: adithyan-r1281
Tags: events, event management, registrations, custom post type
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A custom WordPress events manager with timezone-aware event lifecycle handling, public registration, capacity enforcement, and admin registration management.

== Description ==

Production Events Manager provides a custom event management workflow for WordPress.

Features include:

* Events custom post type.
* Venue taxonomy.
* Start/end dates, registration closing time, capacity, cancellation state, and featured images.
* Upcoming events archive and single-event display.
* Timezone-aware event lifecycle logic.
* Public REST registration endpoint.
* Duplicate registration protection.
* Atomic capacity enforcement for limited-capacity events.
* Admin registration search, filtering, pagination, and CSV export.
* Dedicated registration-management capability.
* Responsive registration UI with accessibility support.
* Data-preserving deactivation and opt-in destructive uninstall.

== Installation ==

1. Upload the `production-events-manager` directory to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins screen.
3. Create an event under Events.
4. Publish the event and visit `/events/`.
5. Open the event and test a registration.
6. Review registrations under Events -> Registrations.

== REST API ==

Endpoint:

`POST /wp-json/production-events/v1/events/{event_id}/registrations`

Request fields:

* `name`
* `email`

Successful requests return HTTP 201.

== Data retention ==

Deactivation and normal uninstall preserve plugin data.

Destructive uninstall is opt-in through the `PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL` constant. Define it as `true` only when permanent deletion is intentional.

== Documentation ==

See the plugin package for:

* `README.md`
* `ARCHITECTURE.md`
* `SECURITY.md`
* `PERFORMANCE.md`
* `TESTING.md`
* `DEMO.md`

== Changelog ==

= 1.0.5 =
* Production events management, registration, admin management, security, accessibility, and lifecycle features.
