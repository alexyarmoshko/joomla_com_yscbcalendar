# CLAUDE.md - Project Guidelines

## Project Overview

This is a Joomla component (`com_yscbcalendar`). This component shows a calendar using the existing events provided 
by Joomla Community Builder CBGroupJive. The calendar view should provide "week" and "month" views based on all events
that are available to a currently logged in user via groups that this user is member of.
There should be a way to scroll calendar forward/back by week/month. Initially calendar shows a week with the current day.
Look and feel of the calendar should be similar to the standard Google calendar. There is should be a button "Today" that
will show week/month with a current day. Each day should show events for that day as a one line event title per event.

The full repository for Joomla including CB modules and components 
is available at "C:\Users\alex\repos\ecskc.eu.sites\test-html", which to used for reference and to understand Joomla
structure and context.

All changes to be stored in the component repository at "C:\Users\alex\repos\joomla_com_yscbcalendar"

Follow Joomla and PHP best practices. [Joomla documentation](https://manual.joomla.org/docs/) for version 5.x and 6.x is to be followed.

## Tech Stack

- **CMS**: Joomla 5.4
- **Language**: PHP 8.3
- **Database**: MariaDB via Joomla Database API

## Coding Standards

### PHP

- Follow [Joomla Coding Standards](https://developer.joomla.org/coding-standards.html)
- Use PSR-12 as the base style guide
- Use strict types: `declare(strict_types=1);`
- Use type hints for parameters and return types
- Use `camelCase` for variables and methods
- Use `PascalCase` for class names
- Prefix module classes with `Mod` (e.g., `ModYscbsubsExpiredlistHelper`)

### File Structure (Joomla 4/5 Module)

## Joomla Best Practices

### Database Queries

- Always use Joomla's Database API (`$db = Factory::getContainer()->get(DatabaseInterface::class);`)
- Use prepared statements with `bind()` for user input
- Use query builder methods (`$query->select()`, `$query->from()`, etc.)

### Security

- Escape output: `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` or `$this->escape()`
- Never trust user input - validate and sanitize all inputs
- Use `JPATH_BASE` constants, never hardcode paths
- Check access with `$user->authorise()`

### Translation

- Use language strings: `Text::_('MOD_MODULENAME_KEY')`
- Define strings in language INI files
- Any strings defined in `*.sys.ini` must also be duplicated in *.ini 
- Use `Text::sprintf()` for strings with placeholders

### Output

- Keep logic out of template files (`tmpl/`)
- Use module helpers for data processing
- Escape all output in templates

## Common Commands

```bash
# Check PHP syntax
php -l mod_modulename.php

# Run PHP CodeSniffer with Joomla standards
phpcs --standard=Joomla src/
```

## Important Notes

- Test on both Joomla 5.x and 6.x if supporting multiple versions
- Use namespaces following Joomla conventions for component and module as required
- Register services in `services/provider.php` for Joomla 4+ when needed
