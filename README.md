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

1. Build the component ZIP (see Packaging below) or download a release from the `installation/` folder.
2. In Joomla Administrator, go to **System > Install Extensions** and upload the ZIP.
3. Create a menu item of type **YS CB Calendar > Calendar View** to display the calendar on the site.

## Configuration

In Joomla Administrator:

- Navigate to **Components > YS CB Calendar > Options**.
- **Default view**: week or month.
- **Week start day**: Sunday or Monday (default: Monday).
- **Time format**: 12-hour or 24-hour (default: 24h).

## Packaging

This repo includes a Makefile for building a release ZIP. Run from the repository root:

    make dist

This creates a versioned ZIP in `installation/` (e.g. `com_yscbcalendar-v1-0-1.zip`) and updates the SHA256 hash in `yscbcalendar.update.xml`.

To remove the generated ZIP:

    make clean

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

docs/                              Documentation
  execution_plan.md                Living design and implementation document
  execution_changelog.md           Version history and change log
```

## License

GPL-2.0-or-later
