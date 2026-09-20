# Testing

## Current testing approach

Automated PHPUnit/Composer setup is intentionally deferred. The project currently uses manual regression testing plus PHP syntax validation.

Automated static analysis and unit/integration tests can be added later without changing the production architecture.

## PHP syntax check

Run a PHP syntax check against every PHP file in the plugin. Every PHP file should report no syntax errors.

Example:

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
```

## Event management checklist

- Create a new event.
- Save valid start/end datetimes.
- Save a valid capacity.
- Save a registration closing datetime equal to or earlier than start.
- Verify invalid start/end combinations are rejected.
- Verify invalid registration closing values are rejected.
- Verify invalid capacity input is not persisted.
- Enable cancellation and verify lifecycle changes.
- Publish the event.

## Archive checklist

- Visit `/events/`.
- Verify only published upcoming events appear.
- Verify expired events are excluded.
- Verify cancelled events are excluded.
- Verify pagination works.
- Verify venue filtering works.
- Verify date filtering/order behavior where configured.
- Filter by venue only, by date range only, and by both; the result count and pagination links keep the filters.
- Verify a single-day range (same From and To date) returns events starting on that day, including late evening ones.
- Verify a From date in the past behaves like no lower bound, and a To date in the past returns the "no match" state.
- Verify From later than To is swapped instead of returning nothing.
- Verify an unknown venue ID or a malformed date in the URL is ignored and the plain archive renders.
- Verify cancelled and expired events never appear, whatever the filters.
- Verify Reset and "Clear filters" return to the unfiltered archive.
- With plain permalinks, verify submitting the form still lands on the events archive.

## Single-event checklist

- Open a published event.
- Verify title, description, image, dates, venue, capacity, and registration closing information.
- Verify the lifecycle status is shown.
- Verify the registration form appears only when registration is open.
- Verify cancelled, expired, started, closed, and capacity-reached states prevent registration.

## Registration checklist

- Submit a valid name/email pair.
- Verify HTTP `201` success.
- Verify the success message and form reset.
- Submit the same email again and verify HTTP `409`.
- Submit invalid email/name values and verify HTTP `400` validation behavior.
- Attempt registration for a missing event and verify `404` behavior.
- Test a full event and verify registration is blocked with the capacity response.
- Verify the registration is stored in the custom registrations table.
- Refresh the page and verify the record remains.

## Admin registrations checklist

- Open **Events → Registrations** as an authorized administrator.
- Filter by event.
- Search by name.
- Search by email.
- Verify pagination.
- Export CSV.
- Open the CSV and verify event, name, email, and registration time.
- Verify formula-like values are protected against CSV injection.
- Verify an unauthorized user cannot access the page/export.

## Timezone checklist

Change the WordPress site timezone and verify event lifecycle calculations follow the WordPress timezone rather than the server timezone.

Test boundary times for:

- event start
- event end
- registration closing

## Lifecycle boundary checklist

Test each state independently:

1. Upcoming.
2. Started.
3. Registration closed.
4. Expired.
5. Cancelled.

Remember that the lifecycle service intentionally evaluates cancellation, expiry, registration closing, and start in that order.

## Lifecycle/uninstall checklist

- Deactivate the plugin and verify data remains.
- Reactivate the plugin and verify events and registrations remain usable.
- Uninstall with the default configuration and verify data remains.
- Only in a disposable test environment, enable `PRODUCTION_EVENTS_DELETE_DATA_ON_UNINSTALL` and verify the documented destructive cleanup.

## Accessibility checklist

- Navigate the registration form using the keyboard only.
- Verify visible focus states.
- Verify every input has a label.
- Verify field errors are associated with their inputs.
- Verify invalid fields receive `aria-invalid`.
- Verify success/error announcements are accessible.
- Verify reduced-motion preferences are respected.
- Verify the layout remains usable on narrow screens.
