# YakShaver CB Calendar (com_yscbcalendar)

A Joomla 5.x component that displays a calendar view of CBGroupJive events for the current user. It provides week and month views, navigation controls, and color-coded events by group.

## Features

- Week and month calendar views
- Previous/next navigation and Today shortcut
- Color-coded events by group
- Event details in a modal popup
- Optional 12h/24h time format and week start configuration

## Requirements

- Joomla 5.4+
- Community Builder with CBGroupJive plugin installed
- PHP 8.3+

## Installation

1. Build or download the component ZIP (see Packaging below).
2. In Joomla Administrator, go to System  Install Extensions and upload the ZIP.
3. Create a menu item pointing to *YS CB Calendar* to display the calendar on the site.

## Configuration

In Joomla Administrator:

- Components  YS CB Calendar  Options
- Set default view (week/month), week start day, and time format.

## Packaging

This repo includes a Makefile for building a release ZIP.

Run from the repository root:

    make dist

This creates a versioned ZIP in `installation/` and updates the SHA256 hash in `yscbcalendar.update.xml`.

## Development notes

- Frontend code lives in `site/`.
- Admin code and config live in `admin/`.
- CSS/JS assets are in `media/com_yscbcalendar/`.

## License

GPL-2.0-or-later
