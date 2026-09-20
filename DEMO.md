# Demo Checklist

This document is a repeatable manual demonstration checklist. It does not contain a fabricated video or claim that a recording exists.

## 1. Setup

- Activate Events Manager Custom.
- Confirm the **Events** menu is available.
- Confirm the registration table exists.

## 2. Create an event

Create a published event with:

- title
- description
- featured image
- venue
- future start datetime
- future end datetime
- finite capacity
- optional registration closing datetime

Save and confirm the values persist after a page refresh.

## 3. Show the archive

Open `/events/` and demonstrate:

- event title
- featured image
- event dates
- venue
- excerpt
- event link
- pagination/filter behavior where applicable

## 4. Show the single event page

Open the event and demonstrate:

- event details
- lifecycle status
- capacity
- registration closing time
- registration form

## 5. Demonstrate registration

Submit a valid name and email.

Verify:

- the request succeeds
- the success message is displayed
- the registration appears in **Events → Registrations**

Submit the same email again and demonstrate duplicate protection.

## 6. Demonstrate lifecycle restrictions

Use test events to demonstrate:

- upcoming
- started
- registration closed
- expired
- cancelled
- capacity reached

Verify registration is unavailable in each applicable state.

## 7. Demonstrate admin management

Show:

- event filter
- name/email search
- pagination
- CSV export

## 8. Demonstrate retention

Deactivate the plugin and verify data remains.

For a disposable test installation only, demonstrate the opt-in destructive uninstall constant if required.
