# WordPress compatibility audit — 2026-11-05

Scope: all 24 files in `DarylMeese/attention-network` main `7d4a49b0d8fda4530afcc04f81e21aedfd511f5c`, checked against platform source `2c49fc6201752cbb8df61a6622ace50143efce5d`. Work was isolated on `codex/wordpress-compatibility-qa`; no platform, existing checkout or live WordPress edits were made by this audit.

## Confirmed problems and repairs

- Activation/settings pointed to localhost. They now default to the canonical public HTTPS API, validate URL/UUID fields, block unsafe destinations and redirects, and keep TLS verification enabled.
- A global pasted credential was mislabeled as an API key. Menu routes actually require a user JWT and scoped RBAC. The new native platform sign-in stores only encrypted per-WordPress-user/session/API/org credentials; it never stores a password or returns tokens to browser JavaScript. The retired shared credential and exact legacy cache namespace are removed on an administrator visit.
- Credential-independent transients could expose data after changing users or settings. All API reads now bypass shared transient caches. Public reads do not even load/refresh a private session.
- Public menu shortcodes fetched protected management data, including inactive menus. The missing public Site UUID setting now selects the existing tenant-scoped public list/detail routes. There is no private fallback; explicit inactive/mismatched menu responses are rejected.
- Most menu bodies used unsupported snake-case properties; section reordering used the wrong method/body; current camel-case IDs/prices/settings were not rendered. These are mapped at the client boundary. Native nested parent-chain routes are preserved. Unrelated item metadata survives an edit, and a null price stays hidden instead of becoming $0.00.
- Sunday was sent as day 0 instead of 7; closed periods sent times instead of null. Hours now preserve the displayed version, return the new version after save, and version special-hours deletion. The one-period editor refuses to flatten existing multiple-period/24-hour schedules. Editing controls respect current platform capabilities.
- Offers, staff and push used nonexistent/incompatible APIs or reported a local copy as a successful platform save. These unsupported actions now fail explicitly and their pages link to the platform. Registered legacy public widgets remain callable but do not publish private/fabricated content.
- Review GET generated fabricated five-star, “verified” customer records. Those records are no longer read/generated. Published reviews come from the real public API; replies use actual scoped POST/PATCH routes after checking the review's organization. The badge shows the published count; dashboard averages are labeled as the reviews shown, without false aggregate/verification claims.
- Website inquiries were falsely described as platform leads and instant dispatch. They are explicitly WordPress-local, do not call the unrelated network lead API, validate contact/status/length input, preserve or explicitly clear notes, and do not report failed persistence as success. The site retains its latest 200 inquiries. Private inquiry options are not autoloaded on new writes.
- Unescaped legacy phone/email values could break out of JavaScript-generated anchor attributes. Links are now built through DOM attribute APIs. The native headless test includes hostile stored values and proves no executable elements are created.
- Local inquiry/portfolio option read-modify-write operations now obtain a bounded database advisory lock and release it in `finally`; a busy/unsupported lock fails visibly. Unknown portfolio deletions and stale edits do not claim success or recreate a deleted record.
- Dialogs now have names, focus entry/return, Tab containment and Escape behavior. Tall dialogs can scroll; focus indicators and small-screen/reduced-motion styles are provided. Inquiry forms load their jQuery dependency, have accessible field/status names and bounded input lengths. Missing shortcode/AJAX callbacks were corrected.

## File-by-file account

| Original file | Review/result |
| --- | --- |
| `.gitignore` | Development/archive exclusions reviewed; runtime ZIP is explicitly allowlisted. |
| `README.md` | Replaced unsupported security/feature claims with current setup, limitations and tests. |
| `readme.txt` | Version and native sign-in setup corrected. |
| `attention-network.php` | Version/default URL corrected; native session helper loaded/initialized. |
| `includes/class-api-client.php` | All outgoing callers mapped; transport/auth/privacy, canonical DTOs, honest unsupported operations and local persistence repaired. |
| `includes/class-settings.php` | Per-user sign-in UI, public Site UUID, typed sanitizers and legacy credential/cache retirement. |
| `includes/class-menu-admin.php` | Canonical input validation, optional price/zero calories, capability UI and unsupported controls removed. |
| `includes/class-business-admin.php` | Sunday/closed/version fixes, preserved complex schedules, current capabilities and missing callback. |
| `includes/class-staff-admin.php` | Unsupported page/actions fail truthfully; no private staff data published. |
| `includes/class-offers-admin.php` | Unsupported page/actions fail truthfully; fake local-success/toggle paths retired. |
| `includes/class-push-admin.php` | No nonexistent push endpoint or fake subscription/broadcast success. |
| `includes/class-reviews-admin.php` | Real published data, bounded detail reads, truthful average/count labels and real reply behavior. |
| `includes/class-leads-admin.php` | WordPress-local scope, real validation, persistence errors and private notes. |
| `includes/class-portfolio-admin.php` | WordPress-local scope and actual create/update/delete errors. |
| `includes/class-shortcodes.php` | Public-only menus/reviews, neutral unsupported widgets, no invented reviews/rating, local inquiry/portfolio rendering and accessible forms. |
| `includes/class-blocks.php` | Public-only menu selection; localized data contains only menu ID/name/status. |
| `assets/js/admin-menu.js` | Native sign-in, version propagation, stored-value DOM safety, read-only controls and keyboard/focus behavior. |
| `assets/js/block.js` | Current normalized menu selector/attributes reviewed; React renders names as text. |
| `assets/css/admin-menu.css` | Modal scrolling, responsive controls, focus visibility and reduced motion. |
| `assets/css/frontend-menu.css` | Existing layout/theme rules reviewed; keyboard focus made visible. |
| `templates/menu-standard.php` | Escaped canonical menu data; null price hidden. |
| `templates/menu-taplist.php` | Escaped canonical metadata/options; null price hidden and pricing header retained. |
| `templates/menu-qr.php` | Escaped canonical data and links; null price hidden. |
| `stubs/wordpress-stubs.php` | IDE-only declarations; not loaded by plugin and excluded from production ZIP. |

New files: encrypted session helper and its focused test, integrated PHP compatibility suite, controlled headless DOM suite, and this audit record.

## Current HTTP contract

- Management: `GET/POST /organizations/{org}/menus`; `GET/PATCH/DELETE /organizations/{org}/menus/{menu}`; native nested section/item/option routes; **PATCH** `.../sections/reorder` with `{sections:[{sectionId,displayOrder}]}`; **PUT** display settings. Existing source aliases verify parent ownership. Actual personal JWT/RBAC remains authoritative; no invented version/idempotency headers or automatic mutation retries.
- Public menus: **GET** `/tenants/{siteUUID}/organizations/{orgUUID}/menus` and `/{menuUUID}` only. The platform checks active site, business eligibility, feature state, menu status and public media.
- Hours: existing organization/location hours and special-hours routes; day 1–7, canonical nullable times and current version. Special-hours DELETE uses the displayed version query.
- Reviews: public organization list and published detail; actual review response POST/PATCH after matching the configured organization. No public credential and no manufactured aggregate.
- Inquiries/portfolio: explicitly WordPress-local, never a fallback for failed platform operations.
- Staff/offers/push/public hours/card widgets: no unsupported outgoing wrapper remains; clear admin explanation/platform link.

## Evidence and limits

- PHP 8.4: **109 offline compatibility checks passed**. These execute real client/renderers/handlers against controlled WordPress transport/storage, covering privacy, DTOs, mutation failure, guards, callbacks, version payloads, unknown/stale records and templates.
- Encrypted session helper: **12 offline cases passed**, including binding changes, tampering, serialized rotation, stale-lock ownership, failed storage, real WordPress metadata unslashing and unconfirmed logout.
- Chromium headless: **17 controlled DOM checks passed** using the actual plugin JavaScript and pinned jQuery 3.7.1, covering keyboard dialogs, private login input handling, honest disconnect failure and stored HTML injection. External requests are blocked; browser processes close in `finally`.
- **20 PHP files passed syntax checks**; both production JavaScript files pass Node syntax checks.
- No real emails, external provider operations or application database writes were used by these tests. Native WordPress/MySQL advisory lock execution, actual platform account sign-in/refresh, permission denial, installed shortcode rendering and CRUD require a controlled dedicated-account installation acceptance run. They are not claimed by offline fixtures.
- A production ZIP excludes tests, stubs, Git metadata and development dependencies. Its file manifest/hash is supplied separately for installation review.

## Native acceptance follow-up

The first real WordPress sign-in exposed a metadata boundary missed by the original offline stub: `update_user_meta` unslashes values, so encrypted JSON needed `wp_slash` before persistence. The old helper rejected the changed byte representation after the API had accepted sign-in. Version 1.1.2 includes that repair, a reproduced failing regression, and the corrected 12-case helper gate. Failed temporary WordPress sessions/privileges were retired; no menu mutation occurred in that failed sign-in attempt. Native acceptance evidence is recorded separately after installing the corrected artifact.
