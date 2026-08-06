# Release Notes

## com_yscbcalendar v1.0.3

**Release date:** 2026-06-09

### Fixed

- Prevented PHP notices when binding formatted calendar date range values in `CalendarModel`.

## com_yscbcalendar v1.0.2

**Release date:** 2026-02-10

This is a security and maintenance release. It fixes a stored XSS vulnerability in the event detail modal, hardens SQL query construction, resolves a deployment bug on Linux, fixes Joomla 5 API compatibility issues, and includes several code quality improvements. All users are encouraged to upgrade.

## Upgrade Instructions

1. Download `com_yscbcalendar-v1-0-2.zip` from the GitHub releases page.
2. In Joomla Administrator, go to **System > Install > Extensions**.
3. Upload the ZIP file. The installer will upgrade the existing component in place.

No database changes or configuration migration is required.

## Security

- **XSS fix: sanitize event description HTML before rendering.** The event detail modal rendered CBGroupJive event descriptions via `innerHTML` without sanitization, allowing stored XSS if a group member injected malicious HTML into an event description. A whitelist-based HTML filter (`Joomla\Filter\InputFilter`) now strips scripts, event handlers, and unsafe attributes while preserving safe formatting tags.

## Changed

- **Consistent parameter binding in CalendarModel.** All user-derived SQL values now use Joomla's prepared statement binding (`$query->bindArray()` for access-level arrays, named `:parameters` for scalars). This replaces the previous mix of inline `implode`/`(int)` casts and bound parameters.

## Fixed

- **Admin view namespace casing mismatch (Linux deployment bug).** The admin view namespace declared `...\View\YSCBCalendar` but the folder is `Yscbcalendar`. On case-sensitive filesystems PSR-4 autoloading would fail. Fixed by correcting the namespace to `...\View\Yscbcalendar`.
- **Joomla 5 InputFilter compatibility.** Replaced the removed static factory method `InputFilter::getInstance()` with a direct constructor call (`new InputFilter(...)`). Also replaced the removed constants `TAGS_WHITELIST` and `ATTR_WHITELIST` with their Joomla 5 equivalents `ONLY_ALLOW_DEFINED_TAGS` and `ONLY_ALLOW_DEFINED_ATTRIBUTES`.
- **Syntax error in week template.** Removed a duplicate `<?php` opening tag in `default_week.php` that caused a "syntax error, unexpected token <" when loading the week view.
- **Login redirect after authentication.** The guest login prompt now includes a `return` parameter so Joomla redirects back to the calendar after login, instead of the site default page.
- **Font Awesome icon consistency.** Migrated five remaining FA4 icon classes to FA6 syntax (`fa-solid`/`fa-regular` prefixes, updated icon names).
- **Hardcoded version in admin dashboard.** The dashboard now reads the installed version dynamically from the component manifest cache.
- **Hardcoded CSS color.** Replaced `#fafafa` for other-month cells with the new `--yscbc-other-month-bg` CSS custom property, consistent with the existing variable-based theming.
- **Week template performance.** Eliminated a duplicate 7-day computation loop by pre-computing day data once and iterating over the result in both the date-number row and event-cell row.
- **Code clarity.** Replaced magic number `3` with `GROUP_TYPE_SECRET` constant; moved `bind()` calls adjacent to the query clauses that reference them; removed dead null-coalescing fallbacks in EventController; removed an unused variable.

## Files Changed

| File | Change |
| --- | --- |
| `site/src/Controller/EventController.php` | XSS sanitization, InputFilter API fix, dead code removal |
| `site/src/Model/CalendarModel.php` | Parameter binding, bind() placement, `GROUP_TYPE_SECRET` constant |
| `site/src/View/Calendar/HtmlView.php` | _(unchanged in this release)_ |
| `site/tmpl/calendar/default.php` | FA6 icon migration |
| `site/tmpl/calendar/default_week.php` | Pre-computed `$days` array, duplicate `<?php` tag fix, unused variable removal |
| `site/tmpl/calendar/login.php` | Return URL on login link |
| `admin/src/View/Yscbcalendar/HtmlView.php` | Namespace fix, dynamic version lookup |
| `admin/tmpl/yscbcalendar/default.php` | Namespace type-hint fix, dynamic version display |
| `media/com_yscbcalendar/css/calendar.css` | `--yscbc-other-month-bg` CSS variable |
| `yscbcalendar.xml` | Version bump to 1.0.2 |
| `yscbcalendar.update.xml` | Version and download URL bump to 1.0.2 |

## Full Changelog

See the [releases page](https://github.com/alexyarmoshko/joomla_com_yscbcalendar/releases) for the complete version history.
