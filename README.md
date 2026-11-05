# Attention Network for WordPress

Version 1.1.1 connects a WordPress website to an Attention Network organization. The platform remains the source of truth for menus, hours and published reviews.

## Set up

1. Install the `attention-network` plugin folder and activate it.
2. Open **Attention Network → Settings**.
3. Save the API address (`https://app.attentionnetwork.net/api`), the **organization UUID**, and the **public site UUID**. A Relationship ID or site slug cannot replace these UUIDs.
4. Sign in with your own platform account and choose **Check menu access**. WordPress administrator access does not grant platform permissions.

Each WordPress administrator signs in separately. The connection is bound to that WordPress user, login session, organization and API address. Passwords are never stored. Access and refresh tokens are encrypted in private user metadata using the WordPress authentication salt; they never reach browser JavaScript or shared caches. Refresh is serialized and protected mutations are not automatically replayed. An unconfirmed disconnect is shown as a failure that can be retried.

The old shared API-key setting is retired. Organization API keys cannot authorize the platform's menu-management routes. The plugin does not create broader machine permissions or bypass platform roles.

## Available features

- **Menu management:** create, edit and delete menus, sections, items and modifiers; reorder sections; choose display settings.
- **Operating hours:** edit one period per weekday and special dates. Existing multiple-period and 24-hour schedules are edited in the platform to avoid flattening them. Times use the location's effective timezone. Saved versions prevent a stale hours form from overwriting a newer change.
- **Published reviews:** read published reviews and submit/edit owner replies with the platform's review-management permission. The dashboard average is explicitly limited to the reviews shown, rather than claiming a business-wide aggregate.
- **Website inquiries:** WordPress-local intake and status/notes. The latest 200 inquiries are retained. Inquiries are not platform lead-routing records and submitting a form does not book or dispatch a visit. Any configured website email notification uses WordPress mail.
- **Project portfolio:** WordPress-local project records and before/after images. These do not synchronize with the platform.

Local inquiry/portfolio writes use a bounded MySQL/MariaDB advisory lock so simultaneous requests cannot silently overwrite each other's option updates. If the database cannot obtain the lock, the request fails visibly without claiming a save.

## Public content

```text
[attention_menu]
[attention_menu id="MENU-UUID" layout="standard|taplist|qr"]
[attention_reviews limit="6" min_rating="1"]
[attention_rating_badge]
[attention_lead_form type="quote|emergency|consultation"]
[attention_portfolio category="" limit="6"]
[attention_before_after before="https://…" after="https://…"]
```

The Gutenberg **Attention Food Menu** block uses the same public renderer. Public menu reads use only `/tenants/{siteUUID}/organizations/{organizationUUID}/menus` and its menu detail route. The site must be active and the business eligible for that site. No administrator token, inactive-menu fallback, private management response or stale transient is used for public rendering.

`attention_rating_badge` displays the published review count; it does not invent an aggregate rating or verified-review claim. Public review displays default to all star ratings and do not generate sample testimonials.

Staff profiles, contact-card widgets, offer management, public operating-hours widgets and push subscription controls do not have compatible connections in this plugin. Their existing shortcode names remain registered to avoid errors, but produce no visitor content. Administrators see a clear explanation. The corresponding admin pages link to the platform instead of pretending to save or synchronize unsupported actions. Use the supported directory/site integration for those public surfaces.

## Validation

```text
php run_platform_session_tests.php
php run_compatibility_tests.php
node tests/browser-compatibility.cjs --playwright /path/to/playwright-module --jquery /path/to/jquery-3.7.1.min.js
```

The PHP suites are offline and replace WordPress's transport/storage with controlled fixtures. The browser suite uses a headless, isolated DOM and blocks external requests. These checks do not replace installation acceptance against a real WordPress site and a dedicated platform test account.

See [the compatibility audit](docs/compatibility-audit-2026-11-05.md) for the file inventory, current API mapping, evidence and acceptance boundaries.
