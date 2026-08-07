# Release Notes

Release notes for the YakShaver CB Calendar component, newest first. Downloads are on the
[releases page](https://github.com/alexyarmoshko/joomla_com_yscbcalendar/releases).

## 1.0.4

**Release date:** 2026-08-07

A packaging and update-server release. No component code changed; the only difference inside the
package is the update-server URL in the manifest.

### Changed

- **Update server moved.** The extension's update site now points at
  [joomla_update_system](https://github.com/alexyarmoshko/joomla_update_system) instead of this
  repository's `main` branch. Joomla stores the update site **per site at install time**, so sites
  running 1.0.3 or earlier will not be offered this release, or any later one, until their update
  site is corrected — see Upgrade Instructions below.
- **Reproducible release packages.** The package is now built from the git tag with a deterministic
  packager, so its published `sha256` can be re-derived from that tag on any machine rather than
  depending on the machine that built it.

### Fixed

- **Automatic updates never worked.** The update descriptor declared `<client>site</client>`, but
  Joomla registers components under the administrator client. The advertised update therefore never
  bound to the installed component and was filtered out of the update list, so no site has ever been
  offered an update for this component since 1.0.0 — installs and upgrades had to be done by hand.
  The descriptor now declares `<client>administrator</client>`. Sites that polled the old descriptor
  hold a stale entry that must be cleared before the corrected one is picked up; see Upgrade
  Instructions.

## 1.0.3

**Release date:** 2026-06-09

### Fixed

- **PHP notices when binding calendar date range values.** Formatted start and end date strings are now stored in variables before being passed to Joomla query `bind()` calls in `CalendarModel`, avoiding "Only variables should be passed by reference" notices.

## 1.0.2

**Release date:** 2026-02-10

This is a security and maintenance release. It fixes a stored XSS vulnerability in the event detail modal, hardens SQL query construction, resolves a deployment bug on Linux, fixes Joomla 5 API compatibility issues, and includes several code quality improvements. All users are encouraged to upgrade.

### Security

- **XSS fix: sanitize event description HTML before rendering.** The event detail modal rendered CBGroupJive event descriptions via `innerHTML` without sanitization, allowing stored XSS if a group member injected malicious HTML into an event description. A whitelist-based HTML filter (`Joomla\Filter\InputFilter`) now strips scripts, event handlers, and unsafe attributes while preserving safe formatting tags.

### Changed

- **Consistent parameter binding in CalendarModel.** All user-derived SQL values now use Joomla's prepared statement binding (`$query->bindArray()` for access-level arrays, named `:parameters` for scalars). This replaces the previous mix of inline `implode`/`(int)` casts and bound parameters.

### Fixed

- **Admin view namespace casing mismatch (Linux deployment bug).** The admin view declared `...\View\YSCBCalendar` but the folder is `Yscbcalendar`. On case-sensitive filesystems PSR-4 autoloading would fail. Fixed by correcting the namespace to `...\View\Yscbcalendar`.
- **Joomla 5 InputFilter compatibility.** Replaced the removed static factory method `InputFilter::getInstance()` with a direct constructor call (`new InputFilter(...)`). Also replaced the removed constants `TAGS_WHITELIST` and `ATTR_WHITELIST` with their Joomla 5 equivalents `ONLY_ALLOW_DEFINED_TAGS` and `ONLY_ALLOW_DEFINED_ATTRIBUTES`.
- **Syntax error in week template.** Removed a duplicate `<?php` opening tag in `default_week.php` that caused a "syntax error, unexpected token <" when loading the week view.
- **Login redirect after authentication.** The guest login prompt now includes a `return` parameter so Joomla redirects back to the calendar after login, instead of the site default page.
- **Font Awesome icon consistency.** Migrated five remaining FA4 icon classes to FA6 syntax (`fa-solid`/`fa-regular` prefixes, updated icon names).
- **Hardcoded version in admin dashboard.** The dashboard now reads the installed version dynamically from the component manifest cache.
- **Hardcoded CSS color.** Replaced `#fafafa` for other-month cells with the new `--yscbc-other-month-bg` CSS custom property, consistent with the existing variable-based theming.
- **Week template performance.** Eliminated a duplicate 7-day computation loop by pre-computing day data once and iterating over the result in both the date-number row and event-cell row.
- **Code clarity.** Replaced magic number `3` with `GROUP_TYPE_SECRET` constant; moved `bind()` calls adjacent to the query clauses that reference them; removed dead null-coalescing fallbacks in `EventController`; removed an unused variable.

### Files Changed

| File | Change |
| --- | --- |
| `site/src/Controller/EventController.php` | XSS sanitization, InputFilter API fix, dead code removal |
| `site/src/Model/CalendarModel.php` | Parameter binding, bind() placement, `GROUP_TYPE_SECRET` constant |
| `site/tmpl/calendar/default.php` | FA6 icon migration |
| `site/tmpl/calendar/default_week.php` | Pre-computed `$days` array, duplicate `<?php` tag fix, unused variable removal |
| `site/tmpl/calendar/login.php` | Return URL on login link |
| `admin/src/View/Yscbcalendar/HtmlView.php` | Namespace fix, dynamic version lookup |
| `admin/tmpl/yscbcalendar/default.php` | Namespace type-hint fix, dynamic version display |
| `media/com_yscbcalendar/css/calendar.css` | `--yscbc-other-month-bg` CSS variable |
| `yscbcalendar.xml` | Version bump to 1.0.2 |
| `yscbcalendar.update.xml` | Version and download URL bump to 1.0.2 |

## 1.0.1

**Release date:** 2026-01-31

### Fixed

- **Month navigation overflow on end-of-month dates.** PHP's `DateTime::modify('+1 month')` gives incorrect results on dates such as 31 January, overflowing to 3 March because "31 February" does not exist. `getNextUrl()` and `getPrevUrl()` now use `first day of +1 month` / `first day of -1 month`. Week navigation is unaffected, since adding or subtracting seven days never overflows.

## 1.0.0

**Release date:** 2026-01-28

Initial release of the YakShaver CB Calendar component.

### Added

- Full Joomla 5.x MVC component with administrator dashboard and frontend calendar.
- Week and month calendar views with a CSS Grid layout inspired by Google Calendar.
- Previous/next navigation and a "Today" shortcut button.
- Color-coded events by group, using a deterministic hash of the group ID against a 10-color palette.
- Event detail modal (Bootstrap) with AJAX loading, status markers, and group links.
- Keyboard navigation: arrow keys for period navigation, `t` for "Today".
- Touch/swipe navigation for mobile devices.
- A "login required" prompt for guests, instead of an empty calendar.
- Configurable default view (week/month), week start day (Sunday/Monday), and time format (12h/24h) via component options.
- Joomla Web Asset Manager integration for CSS and JS.
- Makefile for automated ZIP packaging with SHA256 hash updates.
- Update XML for Joomla update server support.
- Localized language strings for all UI elements (en-GB).
- Responsive design with a mobile-optimized header layout.

### Changed

- **CBGroupJive "All Events" access rules.** The initial membership-only event query was replaced with the same selection criteria used by CBGroupJive's "All Events" view: CB user approval and confirmation checks, Joomla user block status, group category access levels, group type visibility, and owner/published status rules. Applied consistently to both `getEvents()` and `getEvent()`.
- **Group legend visibility.** Group legend data uses CBGroupJive "All Groups" access rules, so color assignments cover all visible groups rather than direct memberships only.
- **Navigation button placement.** Previous/next arrows flank the calendar title instead of being grouped together.
- **Mobile header layout.** Font Awesome icons for the Today, Week, and Month buttons, text labels hidden on small screens, and a CSS Grid layout placing the title on the first row with controls below.

### Fixed

- **Empty event end dates.** Events with a NULL or `0000-00-00 00:00:00` end date are treated as single-day events where end equals start, in both the SQL range filtering and the PHP event hydration.
- **MySQL strict mode errors.** Removed empty-string DATETIME comparisons that caused `1525 Incorrect DATETIME value` errors under MySQL strict mode.

## Upgrade Instructions

1. Download the component ZIP for the release you want from the [releases page](https://github.com/alexyarmoshko/joomla_com_yscbcalendar/releases).
2. In Joomla Administrator, go to **System > Install > Extensions**.
3. Upload the ZIP. The installer will upgrade the existing component in place.

No database changes or configuration migration are required for any release to date.

### Upgrading from 1.0.3 or earlier

The update server moved in 1.0.4, and Joomla remembers the update site each site was installed with.
Those sites will therefore report "no updates available" indefinitely. Either:

- install the 1.0.4 ZIP by hand as above, which rewrites the stored update site; or
- go to **System > Update Sites**, open **YakShaver CB Calendar Updates**, and set the location to
  `https://raw.githubusercontent.com/alexyarmoshko/joomla_update_system/refs/heads/main/manifests/com_yscbcalendar.update.xml`.

Then go to **System > Update > Extensions** and use **Clear Cache**. This is needed because of the
`<client>` fix above: the old descriptor left a stale, unattached entry that Joomla will not replace
on its own, and it would otherwise mask the corrected one.

Once both are done, later releases arrive through the normal Joomla updater.
