# Performance

## Event queries

The event repository uses `WP_Query`-style WordPress queries with pagination for archive views. The public archive is limited to published upcoming events rather than loading all historical events.

## Registration queries

The registration table has indexes for:

- `event_id`
- `email`
- `registered_at`
- unique `(event_id, email)`

These support event filtering, duplicate checks, ordering, and the common registration lookup paths.

## Admin pagination

The registrations admin page paginates results instead of loading the full registration table for normal page views.

## CSV export

CSV export currently requests a large result set so that the export can be produced in one operation. This is acceptable for the current expected scale but can become memory-intensive for very large registration tables.

A future scale improvement would be streaming/chunked export rather than loading all export rows into memory.

## Admin event-title lookup

The admin registration table resolves event titles for displayed rows. At the current paginated page size this is acceptable, and WordPress object caching can reduce repeated post lookups.

A future optimization could fetch event titles in a single query if registration volumes or admin page sizes increase significantly.

## Frontend assets

Registration CSS and JavaScript are enqueued only on singular `pem_event` pages. The plugin does not load the registration form assets on unrelated frontend pages.

## Lifecycle calculations

Lifecycle checks read only the required event metadata and use centralized timezone-aware calculations. There is no scheduled task or expensive global scan on every request.

## Capacity concurrency

Limited-capacity registration intentionally uses a transaction and row lock. This adds database work to registration requests but provides correctness under concurrent submissions.

The trade-off is deliberate: capacity correctness is more important than avoiding a small amount of locking work during registration.

## Caching

The current implementation does not introduce custom persistent caches. This keeps lifecycle correctness straightforward because event status and capacity can change immediately from the admin.

## Future scaling considerations

If registration volume grows substantially:

1. Stream CSV exports in chunks.
2. Batch event-title lookups in the admin table.
3. Consider caching expensive count operations where invalidation is well-defined.
4. Monitor database query plans for registration searches.
5. Add automated load/concurrency tests around limited-capacity registration.
