# YakShaver CB Calendar (com_yscbcalendar)

A Joomla 5.x component that displays CBGroupJive events in a Google Calendar-inspired interface. Logged-in users see events from all groups visible to them, rendered in a responsive week or month calendar grid with color-coded entries and modal detail popups.

## Features

- **Week and month views** with CSS Grid layout and configurable week start day
- **Navigation controls**: previous/next period, "Today" shortcut, and week/month toggle
- **Color-coded events** by group using a deterministic 10-color palette
- **Event detail modal** showing title, date/time, location, description, group link, and status markers (ended/active/upcoming)
- **Keyboard navigation**: Left/Right arrows for period navigation, `t` for Today
- **Touch/swipe navigation** for mobile devices
- **Multi-day event support** spanning across calendar cells
- **CBGroupJive access rules**: event visibility mirrors the "All Events" view (user approval, group category access, publication status)
- **Guest handling**: unauthenticated visitors see a login prompt
- **Responsive design** with a compact mobile header using Font Awesome icons
- **Configurable**: default view, week start day (Sunday/Monday), and time format (12h/24h)
- **Joomla update server** support with SHA256 package verification

## Requirements

- Joomla 5.4+
- Community Builder with CBGroupJive plugin installed
- PHP 8.3+

## Installation

1. Build the component ZIP (see Building below), or download the latest release from the [releases page](https://github.com/alexyarmoshko/joomla_com_yscbcalendar/releases).
2. In Joomla Administrator, go to **System > Install Extensions** and upload the ZIP.
3. Create a menu item of type **YS CB Calendar > Calendar View** to display the calendar on the site.

## Configuration

In Joomla Administrator:

- Navigate to **Components > YS CB Calendar > Options**.
- **Default view**: week or month.
- **Week start day**: Sunday or Monday (default: Monday).
- **Time format**: 12-hour or 24-hour (default: 24h).

## Building

Packages are **reproducible**: the ZIP bytes depend only on the packaged files and one timestamp — nothing about the machine that built it — so a release can be rebuilt from its tag and still hash to the `sha256` the update descriptor claims. Needs `make`, `git`, `php` and `node` — no Composer, no `zip` binary; the packager is the vendored `tools/jzip.php`.

One caveat on that claim: packages are deflated (`ZIP_LEVEL=9`), so the compressed bytes come from zlib. That is stable across zlib *versions*, but not guaranteed across *implementations* — zlib-ng, shipped as the zlib provider by some distributions, deflates differently. If a third party has to re-derive the checksum with no assumption about the compressor, change `ZIP_LEVEL` to `0` in the [Makefile](Makefile) and commit it — storing rather than deflating is reproducible by construction, at about 3.4× the size. It has to be committed rather than passed on the command line: `dist_release` refuses a command-line `ZIP_LEVEL`, because anything that decides the published bytes must come from the tagged Makefile or a clean checkout of the tag cannot reproduce them.

| Target | What it does |
| --- | --- |
| `make info` | Shows the version, the packaged file list, and the output paths. |
| `make lint` | Syntax-checks every shipped PHP and XML file, the update template, and `media/com_yscbcalendar/js/calendar.js`. |
| `make test` | Placeholder — this repository has no automated harness; release verification is manual. |
| `make release` | Validates (clean tree, unused tag, release notes, test, lint) and tags the manifest `<version>`. |
| `make dist_release` | Packages **that tag** into `installation/release/`, and writes the update descriptor beside it. |
| `make dist_dev` | Packages the **working tree** into `installation/dev/` for a test site; strips `<updateservers>` so the test install cannot update over itself. |
| `make clean` | Removes `build/` and both package directories. |

What ships is one explicit list (`PACKAGE_FILES` in the [Makefile](Makefile)) — never a directory, so a stray file cannot be published by accident. The trade is that a new source file is **silently left out** until it is added to that list; the build only fails the other way round, when a listed file is missing. Check `make info` after adding one.

To cut a release: bump `<version>` in `yscbcalendar.xml` and in `com_yscbcalendar.update.xml`, add the `## <version>` section to [docs/RELEASE.md](docs/RELEASE.md), commit, then `make release && make dist_release`. Upload **that exact ZIP** as the release asset, then publish the generated `installation/release/com_yscbcalendar.update.xml` at the location the manifest's `<updateservers>` entry points to — [joomla_update_system](https://github.com/alexyarmoshko/joomla_update_system), as `manifests/com_yscbcalendar.update.xml` — in that order, since a descriptor published before its asset announces a download that 404s.

The tracked `com_yscbcalendar.update.xml` is a **template**: its `<sha256>` is a 64-zero placeholder and the build refuses to run without it. The real checksum only exists once the package is built, so the published descriptor is a build artifact and is never committed here.

## Project Structure

```text
site/                              Frontend component
  src/Controller/                  DisplayController, EventController (AJAX)
  src/Model/CalendarModel.php      Event retrieval with CBGroupJive access rules
  src/View/Calendar/HtmlView.php   Calendar rendering and navigation
  src/Dispatcher/Dispatcher.php    Component dispatcher
  tmpl/calendar/                   Templates: default, week, month, login
  language/en-GB/                  Site language strings

admin/                             Administrator component
  services/provider.php            Dependency injection
  src/Extension/                   Component boot class
  src/Controller/                  Admin display controller
  src/View/Yscbcalendar/           Admin dashboard view
  tmpl/yscbcalendar/               Admin templates
  language/en-GB/                  Admin language strings
  config.xml                       Component configuration fields
  access.xml                       Access control rules

media/com_yscbcalendar/            Frontend assets
  css/calendar.css                 Google Calendar-inspired styles
  js/calendar.js                   Keyboard, touch, and modal interactivity

tools/                             Build tooling (never shipped)
  jzip.php                         Deterministic ZIP writer

docs/                              Documentation
  RELEASE.md                       Release notes and upgrade instructions
```

## License

GPL-2.0-or-later
