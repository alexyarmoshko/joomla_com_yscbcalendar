# com_yscbcalendar: CBGroupJive Calendar Component for Joomla 5.x

This Execution Plan is a living document. The sections `Progress`, `Surprises & Discoveries`, `Decision Log`, and `Outcomes & Retrospective` must be kept up to date as work proceeds.

This document must be maintained in accordance with `.agent/PLANS.md`.

## Purpose / Big Picture

After this implementation, Joomla users will have a Google Calendar-like component that displays all CBGroupJive events from groups they are members of. Users will be able to:

1. View their events in a **week view** (default) or **month view**
2. Navigate forward/backward by week or month
3. Click "Today" to return to the current date
4. See events color-coded by group membership
5. Click any event to navigate to the full CBGroupJive event page

The administrator will be able to configure component settings through the Joomla backend.

**How to see it working:** After installation, create a menu item pointing to the calendar component. A logged-in user who is a member of one or more CBGroupJive groups will see their events displayed in a calendar grid, styled similarly to Google Calendar.

## Progress

- [x] (2026-01-27) Milestone 1: Project scaffolding and manifest
- [x] (2026-01-27) Milestone 2: Backend component structure and configuration
- [x] (2026-01-27) Milestone 3: Frontend Model - Event data retrieval
- [x] (2026-01-27) Milestone 4: Frontend View - Calendar rendering (Week view)
- [x] (2026-01-27) Milestone 5: Frontend View - Month view
- [x] (2026-01-27) Milestone 6: JavaScript navigation and interactivity
- [x] (2026-01-27) Milestone 7: CSS styling (Google Calendar-like appearance)
- [ ] Milestone 8: Integration testing and validation (requires deployment to Joomla)
- [x] (2026-01-27) Milestone 9: Packaging and distribution (Makefile, update XML)
- [x] (2026-01-28) Improvement 3: Align calendar event selection with CBGroupJive "All Events" view criteria
- [x] (2026-01-28) Improvement 4: Move previous/next buttons to flank the calendar title
- [x] (2026-01-28) Improvement 5: Treat missing event end dates as start dates
- [x] (2026-01-28) Improvement 6: Avoid MySQL strict errors for NULL/zero event end dates
- [x] (2026-01-28) Improvement 7: Compact mobile header with Font Awesome icons

## Surprises & Discoveries

- **Observation:** SQL install/uninstall scripts not needed
  - **Evidence:** Component only reads from existing CBGroupJive tables; no custom tables required. Removed SQL file references from manifest.

- **Observation:** Joomla 5.x component structure requires strict PSR-4 namespacing
  - **Evidence:** Namespace path must match folder structure exactly: `Joomla\Component\YSCBCalendar\Site` maps to `site/src/`

- **Observation:** CSS grid columns can expand due to long event titles even with text truncation
  - **Evidence:** `1fr` tracks honor min-content sizing; switching to `minmax(0, 1fr)` and setting `min-width: 0` on grid items keeps uniform column widths.

- **Observation:** CBGroupJive "All Events" view filters by CB user approval/confirmation and group category access
  - **Evidence:** `component.cbgroupjiveevents.php` applies `cb.approved = 1`, `cb.confirmed = 1`, `j.block = 0`, and category access checks even for event owners.

- **Observation:** Some CBGroupJive events have an empty end date
  - **Evidence:** Events with only `start` populated can appear in the calendar data; treating `end` as `start` prevents them from being filtered out or causing date parsing issues.

- **Observation:** MySQL strict mode errors on DATETIME comparison with empty string literals
  - **Evidence:** Production MySQL raised `1525 Incorrect DATETIME value: ''` when the query used `NULLIF(e.end, '')` on DATETIME columns.

## Decision Log

- **Decision:** Use frontend-only event retrieval without caching
  - **Rationale:** CBGroupJive events are dynamic and group memberships can change; fresh queries ensure accuracy
  - **Date/Author:** 2026-01-27 / Initial planning

- **Decision:** Color assignment uses deterministic hash of group ID
  - **Rationale:** Ensures consistent colors across page loads without storing color mappings
  - **Date/Author:** 2026-01-27 / Initial planning

- **Decision:** Use Joomla Web Asset Manager for CSS/JS
  - **Rationale:** Follows Joomla 5.x best practices; allows template overrides
  - **Date/Author:** 2026-01-27 / Initial planning

- **Decision:** Implement CBGroupJive "All Events" access rules directly in `CalendarModel` with SQL joins and filters
  - **Rationale:** Keeps calendar selection consistent with CBGroupJive without requiring CB plugin runtime globals
  - **Date/Author:** 2026-01-28 / Implementation

- **Decision:** Treat CBGroupJive moderator check as Joomla `core.admin` fallback
  - **Rationale:** Avoids hard dependency on CB runtime while still granting broader access to super users
  - **Date/Author:** 2026-01-28 / Implementation

- **Decision:** Use CBGroupJive "all groups" visibility rules for legend color sources
  - **Rationale:** Keeps group colors consistent even when events are visible outside direct memberships
  - **Date/Author:** 2026-01-28 / Implementation

- **Decision:** Treat missing/empty event end dates as the same value as start
  - **Rationale:** Ensures single-date events are displayed consistently and remain within range filtering
  - **Date/Author:** 2026-01-28 / Implementation

- **Decision:** Remove empty-string DATETIME comparisons in SQL filters
  - **Rationale:** MySQL strict mode rejects `''` as DATETIME; NULL/zero dates are handled safely via `NULLIF(e.end, '0000-00-00 00:00:00')`
  - **Date/Author:** 2026-01-28 / Implementation

## Outcomes & Retrospective

### Implementation Complete (2026-01-27)

**Achievements:**
- Full Joomla 5.x MVC component structure created
- Backend dashboard with configuration options (default view, week start day, time format)
- Frontend calendar with week and month views
- CBGroupJive events integration via database queries
- Color-coded events by group
- Keyboard navigation (arrow keys, 't' for today)
- Touch/swipe navigation for mobile
- Google Calendar-inspired responsive CSS
- Makefile for automated packaging with SHA256 hash updates
- Update XML for Joomla update server

**Remaining:**
- Milestone 8: Integration testing on live Joomla instance required
- Update download URL after GitHub release is created

**Files Created:** 22 source files across admin, site, and media folders
**Package Size:** ~52KB (compressed)

### Future Improvements

1. **[COMPLETED] Event title trimming for consistent cell sizes** - Event titles displayed in calendar cells are trimmed to fit the cell dimensions, ensuring equal cell sizes regardless of event title length. When a title is visually trimmed, an ellipsis ("...") is shown at the end to indicate truncation.
   - **Implementation (2026-01-27):** Updated CSS in `calendar.css` to properly support text truncation:
     - Added `min-width: 0` to `.yscbc-event` and `.yscbc-event-title` (required for flex item text truncation)
     - Added `max-width: 100%` to `.yscbc-event` to constrain width within cell
     - Added `white-space: nowrap`, `overflow: hidden`, `text-overflow: ellipsis` to `.yscbc-event-title`
     - Added `flex: 1` to `.yscbc-event-title` to allow it to shrink and truncate properly
   - **Fix (2026-01-27):** Prevented long titles from expanding grid columns by:
     - Switching grid tracks to `repeat(7, minmax(0, 1fr))` in week/month headers and bodies
     - Adding `min-width: 0` to `.yscbc-grid-cell` and `.yscbc-grid-cell-month`
   - Cells now maintain uniform widths in both week and month views

2. **[COMPLETED] Event popup modal instead of direct link** - When an event is clicked in the calendar, display the event details in a popup modal box instead of navigating to the CBGroupJive event page.
   - **Implementation (2026-01-27):**
     - Created `site/src/Controller/EventController.php` with `getEvent()` AJAX action for fetching event details
     - Added `getEvent()` method to `CalendarModel` for single event retrieval with permission checks
     - Updated `default.php` template to include Bootstrap modal HTML structure and AJAX URL with CSRF token
      - Updated `default_week.php` and `default_month.php` to add `data-event-id` and `data-event-url` attributes to event links
      - Extended `calendar.js` with modal functionality:
        - `initEventModal()` - initializes Bootstrap modal and click handlers
        - `showEventModal()` - fetches event data via AJAX and displays in modal
        - `populateModalContent()` - fills modal header with event title, body with date/time, location, description, group name
        - Modal content aligned to CBGroupJive event markup (`gjGroupEvent*` classes) for shared styling
        - Status-specific markers and styling (pending/active/expired) should mirror `events.php` (`gjGroupEventExpired`, `gjGroupEventActive`, pending icon, and border classes)
        - Group name rendered as a link to the group page, opening in the parent window and closing the modal on click
        - Automatic fallback to direct navigation if Bootstrap is unavailable
     - Added modal CSS styles to `calendar.css`:
       - Color bar matching event/group color
       - Clean layout with icons for date, time, location, and group
       - Loading spinner and error states
       - Responsive design for mobile
     - Added language strings for modal UI elements
   - Features:
     - Bootstrap modal with event title and footer close button
     - Shows: date/time, location (if set), description (as HTML), group name (linked to group page)
     - Status markers and styling match CBGroupJive event cards (pending icon, active/expired classes)
     - Loading spinner while fetching data
     - Error handling with user-friendly messages
     - Keyboard accessible (Escape to close, focus trapping handled by Bootstrap)
     - CSRF token protection for AJAX requests

3. **[COMPLETED] Align event selection with CBGroupJive "All Events" view** - Replace the current event query logic with the same selection criteria used by the CBGroupJive Events plugin's "All Events" view. This ensures consistency between the calendar display and the standard events listing.
   - **Implementation (2026-01-28):**
     - Updated `CalendarModel` queries to use CBGroupJive "All Events" filters (group/category access, user approval/confirmed, user block checks, and published vs. owner access)
     - Added category access checks based on Joomla view levels and included uncategorized groups by default
     - Preserved date range filtering while widening group visibility to match CBGroupJive rules
     - Applied the same access filters to `getEvent()` for modal requests
   - **Extension (2026-01-28):**
     - Updated group legend data to use the same access rules as `groups/allgroups`, so color assignments are based on all visible groups, not only memberships

4. **Move prev/next buttons to flank the title** - Relocate the navigation arrows so the previous button sits to the left of the `<h2 class="yscbc-title">` and the next button sits to the right, matching the requested header layout.
   - **Implementation (2026-01-28):**
     - Moved prev/next links into a new `yscbc-title-nav` wrapper flanking the title
     - Adjusted header CSS to center the title group and keep arrows adjacent on mobile and desktop

5. **[COMPLETED] Normalize empty event end dates** - Events with an empty `end` value should be treated as single-day events where `end` equals `start`.
   - **Implementation (2026-01-28):**
     - Updated `CalendarModel` range filtering to use an effective end date expression that falls back to the start date when `end` is empty
     - Hydrated event objects with `end_date` equal to `start_date` when `end` is blank or `0000-00-00 00:00:00`

6. **[COMPLETED] Avoid MySQL strict errors for NULL/zero end dates** - Ensure SQL range filtering does not compare DATETIME columns to empty string literals.
   - **Implementation (2026-01-28):**
     - Replaced `NULLIF(e.end, '')` usage with a zero-date-only `NULLIF` expression to keep MySQL strict mode happy

7. **[COMPLETED] Compact mobile header with icons** - Use Font Awesome icons for Today/Week/Month and adjust header alignment to fit small screens.
   - **Implementation (2026-01-28):**
     - Added icon spans for Today, Week, and Month buttons and hid text labels on small screens
     - Switched the mobile header layout to a grid with the title on the first row and controls aligned below

## Context and Orientation

### Repository Structure

This component will be developed in: `C:\Users\alex\repos\joomla_com_yscbcalendar`

Reference Joomla installation with CBGroupJive: `C:\Users\alex\repos\ecskc.eu.sites\test-html`

### CBGroupJive Database Tables

The component reads from these existing tables (prefixed with `jos_` or Joomla's configured prefix):

1. **`#__groupjive_plugin_events`** - Events table
   - `id` (INT) - Event ID
   - `user_id` (INT) - Creator user ID
   - `group` (INT) - Group ID (FK to groups table)
   - `title` (VARCHAR 255) - Event title
   - `event` (TEXT) - Event description/HTML content
   - `location` (VARCHAR 255) - Location name
   - `address` (VARCHAR 255) - Physical address
   - `start` (DATETIME) - Event start date/time
   - `end` (DATETIME) - Event end date/time
   - `published` (INT) - 1=published, 0=unpublished

2. **`#__groupjive_groups`** - Groups table
   - `id` (INT) - Group ID
   - `name` (TEXT) - Group name
   - `published` (INT) - Publication status

3. **`#__groupjive_users`** - User-Group membership table
   - `id` (INT) - Record ID
   - `user_id` (INT) - Joomla user ID
   - `group` (INT) - Group ID
   - `status` (INT) - 0=applied, 1=approved, 2=moderator, 3=admin, 4=owner

### Key Query Pattern

To retrieve events for the current user:

```sql
SELECT
    e.id, e.title, e.location, e.start, e.end,
    g.id as group_id, g.name as group_name
FROM #__groupjive_plugin_events e
INNER JOIN #__groupjive_groups g ON e.group = g.id
INNER JOIN #__groupjive_users u ON g.id = u.group
WHERE
    u.user_id = :currentUserId
    AND u.status >= 1          -- Approved members and above
    AND e.published = 1
    AND g.published = 1
    AND e.start >= :rangeStart
    AND e.start <= :rangeEnd
ORDER BY e.start ASC
```

### CBGroupJive Event URL Pattern

Events link to: `index.php?option=com_comprofiler&view=pluginclass&plugin=cbgroupjiveevents&action=events.show&func=show&id={event_id}&group={group_id}`

Or use SEF routing if available through CB's router.

### Joomla 5.x Component Namespace Convention

- **Namespace root:** `Joomla\Component\YSCBCalendar`
- **Frontend (Site):** `Joomla\Component\YSCBCalendar\Site\`
- **Backend (Administrator):** `Joomla\Component\YSCBCalendar\Administrator\`

## Plan of Work

### Milestone 1: Project Scaffolding and Manifest

Create the basic component file structure and installation manifest.

**Files to create:**

```
com_yscbcalendar/
├── yscbcalendar.xml                          # Component manifest
├── yscbcalendar.update.xml                   # Joomla update server XML
├── Makefile                                  # Build/packaging automation
├── installation/                             # Output folder for ZIP packages
│   └── com_yscbcalendar-v{VERSION}.zip       # Versioned installation package
├── site/
│   ├── src/
│   │   ├── Controller/
│   │   │   └── DisplayController.php
│   │   ├── Model/
│   │   │   └── CalendarModel.php
│   │   ├── View/
│   │   │   └── Calendar/
│   │   │       └── HtmlView.php
│   │   ├── Dispatcher/
│   │   │   └── Dispatcher.php
│   │   └── Service/
│   │       └── Router.php
│   ├── tmpl/
│   │   └── calendar/
│   │       ├── default.php
│   │       ├── default.xml
│   │       ├── default_week.php
│   │       └── default_month.php
│   └── language/
│       └── en-GB/
│           └── com_yscbcalendar.ini
├── admin/
│   ├── services/
│   │   └── provider.php
│   ├── src/
│   │   ├── Controller/
│   │   │   └── DisplayController.php
│   │   ├── View/
│   │   │   └── YSCBCalendar/
│   │   │       └── HtmlView.php
│   │   └── Extension/
│   │       └── YSCBCalendarComponent.php
│   ├── tmpl/
│   │   └── yscbcalendar/
│   │       └── default.php
│   ├── language/
│   │   └── en-GB/
│   │       ├── com_yscbcalendar.ini
│   │       └── com_yscbcalendar.sys.ini
│   ├── access.xml
│   └── config.xml
└── media/
    └── com_yscbcalendar/
        ├── css/
        │   └── calendar.css
        └── js/
            └── calendar.js
```

**Manifest file (`yscbcalendar.xml`)** defines:

- Component name, version, author
- PSR-4 namespace declaration
- Frontend and backend file locations
- Language files
- Media folder
- Menu item for admin

### Milestone 2: Backend Component Structure

Create the administrator-side component with:

- Service provider for dependency injection
- Main component extension class
- Configuration options (via `config.xml`)
- Access control (via `access.xml`)
- Simple dashboard view showing component info

**Configuration options to include:**

- Default view (week/month)
- Week start day (Sunday/Monday)
- Time format (12h/24h)
- Number of colors for group palette

### Milestone 3: Frontend Model - Event Data Retrieval

Implement `CalendarModel` that:

1. Gets the current user ID from Joomla session
2. Queries user's group memberships from `#__groupjive_users`
3. Retrieves events from `#__groupjive_plugin_events` within a date range
4. Joins with `#__groupjive_groups` to get group names
5. Returns structured event data with: id, title, start, end, location, group_id, group_name, color

**Model methods:**

- `getEvents($startDate, $endDate)` - Returns array of event objects
- `getUserGroups()` - Returns array of group IDs/names user belongs to
- `generateGroupColor($groupId)` - Returns hex color based on group ID hash

### Milestone 4: Frontend View - Week View

Implement `HtmlView` and week template that:

1. Calculates the week boundaries (Monday-Sunday or Sunday-Saturday based on config)
2. Displays a 7-column grid with day headers showing date
3. Highlights current day
4. Renders events as colored blocks with title text
5. Includes navigation controls (prev week, next week, today)
6. Includes view switcher (week/month toggle)

**Template structure (`default_week.php`):**

- Header row with day names and dates
- Grid cells for each day
- Events rendered as clickable links within cells
- Navigation controls

### Milestone 5: Frontend View - Month View

Implement month template that:

1. Calculates month boundaries and displays full calendar grid
2. Shows 6 rows x 7 columns (handles month overflow)
3. Displays abbreviated event titles (one line per event)
4. Grays out days from adjacent months
5. Highlights current day
6. Uses same navigation pattern as week view

**Template structure (`default_month.php`):**

- Month/year header
- Weekday name row
- Date grid with events
- Navigation controls

### Milestone 6: JavaScript Navigation and Interactivity

Implement client-side functionality:

1. AJAX-based navigation (prev/next/today) without full page reload
2. View switching (week/month) with state preservation
3. Keyboard navigation (arrow keys for week/month navigation)
4. Responsive behavior for mobile

**JavaScript module (`calendar.js`):**

- `initCalendar()` - Initialize calendar with current date
- `navigateWeek(direction)` - Move week forward/backward
- `navigateMonth(direction)` - Move month forward/backward
- `goToToday()` - Return to current date
- `switchView(viewType)` - Toggle between week/month
- `fetchEvents(start, end)` - AJAX call to retrieve events

**AJAX endpoint:**
Create a JSON response controller action that returns events for a date range.

### Milestone 7: CSS Styling

Create Google Calendar-inspired styles:

1. Clean, minimalist grid layout
2. Color palette for groups (8-10 distinct colors)
3. Event blocks with rounded corners, group color background
4. Hover effects on events
5. Clear visual distinction for "today"
6. Responsive design for mobile devices

**CSS variables for theming:**

```css
--yscbc-bg-color: #ffffff;
--yscbc-border-color: #dadce0;
--yscbc-today-bg: #e8f0fe;
--yscbc-event-text: #ffffff;
--yscbc-header-text: #70757a;
```

**Color palette for groups:**

Use 10 predefined colors assigned by `group_id % 10`:

- #039be5, #7986cb, #33b679, #8e24aa, #e67c73
- #f6bf26, #f4511e, #039be5, #616161, #3f51b5

### Milestone 8: Integration Testing

Verify the component works correctly:

1. Install component via Joomla extension manager
2. Create menu item pointing to calendar
3. Log in as user with group memberships
4. Verify events display correctly
5. Test navigation (week/month, prev/next, today)
6. Test event click navigation to CBGroupJive
7. Verify colors are consistent per group
8. Test with user having no group memberships (empty state)
9. Test with guest user (access denied or login prompt)

### Milestone 9: Packaging and Distribution

Create build automation and update server support for easy installation and updates.

**Files to create:**

1. **`Makefile`** - Automates packaging:
   - Extracts version from `yscbcalendar.xml` manifest
   - Creates versioned ZIP file (e.g., `com_yscbcalendar-v1-0-0.zip`)
   - Places ZIP in `installation/` folder
   - Computes SHA256 hash and updates `yscbcalendar.update.xml`

2. **`yscbcalendar.update.xml`** - Joomla update server XML:
   - Defines component metadata for Joomla's update system
   - Contains download URL pointing to GitHub releases
   - Includes SHA256 hash for package verification
   - Specifies target Joomla platform versions

3. **`installation/`** - Output directory for distribution packages

**Makefile targets:**

- `make dist` - Build the distribution ZIP and update SHA256 hash
- `make clean` - Remove generated ZIP file

**Package contents:** The ZIP includes only distribution files:
- `yscbcalendar.xml` (manifest)
- `site/` (frontend component)
- `admin/` (backend component)
- `media/` (CSS/JS assets)

**Excluded from package:** Development files like `.git/`, `.agent/`, `Makefile`, `installation/`, `*.update.xml`, documentation.

## Concrete Steps

### Step 1: Create Directory Structure

Working directory: `C:\Users\alex\repos\joomla_com_yscbcalendar`

Create all required directories:

```shell
    mkdir -p site/src/Controller
    mkdir -p site/src/Model
    mkdir -p site/src/View/Calendar
    mkdir -p site/src/Dispatcher
    mkdir -p site/src/Service
    mkdir -p site/tmpl/calendar
    mkdir -p site/language/en-GB
    mkdir -p admin/services
    mkdir -p admin/src/Controller
    mkdir -p admin/src/View/YSCBCalendar
    mkdir -p admin/src/Extension
    mkdir -p admin/tmpl/yscbcalendar
    mkdir -p admin/language/en-GB
    mkdir -p media/com_yscbcalendar/css
    mkdir -p media/com_yscbcalendar/js
```

### Step 2: Create Manifest File

Create `yscbcalendar.xml` with component metadata, file declarations, and namespace registration.

### Step 3: Create Service Provider

Create `admin/services/provider.php` to register MVCFactory and component services.

### Step 4: Create Component Extension Class

Create `admin/src/Extension/YSCBCalendarComponent.php` as the main component class.

### Step 5: Create Backend Controller and View

Create minimal admin dashboard showing component status.

### Step 6: Create Frontend Controller

Create `site/src/Controller/DisplayController.php` for frontend routing.

### Step 7: Create Calendar Model

Create `site/src/Model/CalendarModel.php` with event retrieval logic.

### Step 8: Create Calendar View

Create `site/src/View/Calendar/HtmlView.php` with view preparation logic.

### Step 9: Create Templates

Create `site/tmpl/calendar/default.php`, `default_week.php`, and `default_month.php`.

### Step 10: Create CSS and JavaScript

Create `media/com_yscbcalendar/css/calendar.css` and `media/com_yscbcalendar/js/calendar.js`.

### Step 11: Create Language Files

Create all `.ini` language files with translation strings.

### Step 12: Create Configuration Files

Create `admin/access.xml` and `admin/config.xml` for ACL and settings.

### Step 13: Create Makefile

Create `Makefile` for packaging automation. The Makefile:

- Extracts version from manifest XML using awk
- Converts version dots to hyphens for filename (1.0.0 → 1-0-0)
- Creates ZIP in `installation/` folder
- Computes SHA256 and updates the update XML file

### Step 14: Create Update XML

Create `yscbcalendar.update.xml` for Joomla update server. Contains:

- Component name and description
- Element name (`com_yscbcalendar`)
- Extension type (`component`)
- Current version
- Download URL (GitHub releases)
- SHA256 hash (updated by Makefile)
- Target platform regex for Joomla 5.x compatibility

### Step 15: Create Installation Directory

Create empty `installation/` directory and add to `.gitignore` pattern for ZIP files.

### Step 16: Build and Verify Package

Run `make dist` to:

1. Generate `installation/com_yscbcalendar-v1-0-0.zip`
2. Update SHA256 hash in `yscbcalendar.update.xml`
3. Verify ZIP contains correct files

## Validation and Acceptance

### Installation Test

1. Package component files into `com_yscbcalendar.zip`
2. Install via Joomla Administrator > System > Extensions > Install
3. Expected: Installation completes without errors

### Backend Access Test

1. Navigate to Administrator > Components > YS CB Calendar
2. Expected: Dashboard displays with component information

### Frontend Display Test

1. Create menu item: Menus > [Menu] > Add > YS CB Calendar > Calendar View
2. Navigate to the menu item as logged-in user
3. Expected: Calendar displays with week view, current week visible

### Event Display Test

1. Ensure test user is member of at least one CBGroupJive group with events
2. Navigate to calendar
3. Expected: Events from user's groups appear with correct titles and colors

### Navigation Test

1. Click "Next" button
2. Expected: Calendar advances by one week (week view) or one month (month view)
3. Click "Today" button
4. Expected: Calendar returns to current date
5. Click "Month" toggle
6. Expected: View switches to month display

### Event Link Test

1. Click on any event in the calendar
2. Expected: Browser navigates to CBGroupJive event detail page

### Empty State Test

1. Log in as user with no group memberships
2. Navigate to calendar
3. Expected: Calendar displays with message "No events to display" or similar

### Guest Access Test

1. Log out and navigate to calendar as guest
2. Expected: Redirect to login or "Please log in to view your calendar" message

### Packaging Test

1. Run `make dist` from repository root
2. Expected: `installation/com_yscbcalendar-v1-0-0.zip` is created
3. Verify ZIP contains: `yscbcalendar.xml`, `site/`, `admin/`, `media/`
4. Verify `yscbcalendar.update.xml` has updated SHA256 hash
5. Install the generated ZIP via Joomla extension manager
6. Expected: Component installs successfully

## Idempotence and Recovery

- All steps can be re-run safely; files are overwritten if they exist
- The component can be uninstalled and reinstalled without side effects
- No database tables are created (reads existing CBGroupJive tables)
- If installation fails, uninstall via Joomla and retry

## Artifacts and Notes

### Database Query (for reference during Model development)

```sql
    SELECT
        e.id,
        e.title,
        e.location,
        e.start,
        e.end,
        g.id as group_id,
        g.name as group_name
    FROM jos_groupjive_plugin_events e
    INNER JOIN jos_groupjive_groups g ON e.group = g.id
    INNER JOIN jos_groupjive_users u ON g.id = u.group
    WHERE
        u.user_id = 42
        AND u.status >= 1
        AND e.published = 1
        AND g.published = 1
        AND e.start >= '2026-01-01 00:00:00'
        AND e.end <= '2026-01-31 23:59:59'
    ORDER BY e.start ASC
```

### CBGroupJive Event URL Construction

```php
    $eventUrl = Route::_(
        'index.php?option=com_comprofiler'
        . '&view=pluginclass'
        . '&plugin=cbgroupjiveevents'
        . '&action=events.show'
        . '&func=show'
        . '&id=' . (int) $event->id
        . '&group=' . (int) $event->group_id
    );
```

### Color Generation Function

```php
    function generateGroupColor(int $groupId): string
    {
        $colors = [
            '#039be5', '#7986cb', '#33b679', '#8e24aa', '#e67c73',
            '#f6bf26', '#f4511e', '#039be5', '#616161', '#3f51b5'
        ];
        return $colors[$groupId % count($colors)];
    }
```

### Makefile Template

```makefile
COMPONENT_NAME := com_yscbcalendar
MANIFEST := yscbcalendar.xml
UPDATE_XML := yscbcalendar.update.xml
INSTALL_DIR := installation

VERSION := $(shell awk -F'[<>]' '/<version>/{print $$3; exit}' $(MANIFEST))

ZIP_VERSION := $(subst .,-,$(VERSION))
ZIP_NAME := $(COMPONENT_NAME)-v$(ZIP_VERSION).zip
ZIP_PATH := $(INSTALL_DIR)/$(ZIP_NAME)

PACKAGE_FILES := $(MANIFEST) site admin media

.PHONY: dist clean

dist: $(ZIP_PATH)
	@SHA256=$$(shasum -a 256 "$(ZIP_PATH)" | awk '{print $$1}'); \
	awk -v sha="$$SHA256" '{ \
		if ($$0 ~ /<sha256>[^<]+<\/sha256>/) { \
			sub(/<sha256>[^<]+<\/sha256>/, "<sha256>" sha "</sha256>"); \
		} \
		print; \
	}' "$(UPDATE_XML)" > "$(UPDATE_XML).tmp" && mv "$(UPDATE_XML).tmp" "$(UPDATE_XML)" && \
	echo "Updated $(UPDATE_XML) sha256 to $$SHA256"

$(ZIP_PATH): $(PACKAGE_FILES)
	@mkdir -p $(INSTALL_DIR)
	@rm -f "$(ZIP_PATH)"
	@cd "$(CURDIR)" && zip -r -X "$(ZIP_PATH)" $(PACKAGE_FILES) -x "*.DS_Store" -x "*/.DS_Store"

clean:
	@rm -f "$(ZIP_PATH)"
```

### Update XML Template

```xml
<updates>
    <update>
        <name>YakShaver CB Calendar</name>
        <description>Calendar component displaying CBGroupJive events for user's groups.</description>
        <element>com_yscbcalendar</element>
        <type>component</type>
        <version>1.0.0</version>
        <client>site</client>
        <downloads>
            <downloadurl type="full" format="zip">https://github.com/alexyarmoshko/joomla_com_yscbcalendar/releases/download/1.0.0/com_yscbcalendar-v1-0-0.zip</downloadurl>
        </downloads>
        <tags>
            <tag>stable</tag>
        </tags>
        <sha256>PLACEHOLDER_HASH</sha256>
        <targetplatform name="joomla" version="((5\.(0|1|2|3|4|5|6|7|8|9))|(6\.(0|1|2|3|4|5|6|7|8|9)))" />
    </update>
</updates>
```

## Interfaces and Dependencies

### External Dependencies

- **Joomla CMS 5.4+** - Core framework
- **Community Builder with CBGroupJive plugin** - Provides event data
- **PHP 8.3+** - Runtime

### Key Interfaces

**In `site/src/Model/CalendarModel.php`:**

```php
    namespace Joomla\Component\YSCBCalendar\Site\Model;

    use Joomla\CMS\MVC\Model\BaseDatabaseModel;

    class CalendarModel extends BaseDatabaseModel
    {
        public function getEvents(\DateTimeInterface $start, \DateTimeInterface $end): array;
        public function getUserGroups(): array;
    }
```

**In `site/src/View/Calendar/HtmlView.php`:**

```php
    namespace Joomla\Component\YSCBCalendar\Site\View\Calendar;

    use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

    class HtmlView extends BaseHtmlView
    {
        protected array $events = [];
        protected array $groups = [];
        protected \DateTimeInterface $currentDate;
        protected string $viewMode = 'week';

        public function display($tpl = null): void;
    }
```

**In `admin/src/Extension/YSCBCalendarComponent.php`:**

```php
    namespace Joomla\Component\YSCBCalendar\Administrator\Extension;

    use Joomla\CMS\Extension\MVCComponent;
    use Joomla\CMS\Extension\BootableExtensionInterface;

    class YSCBCalendarComponent extends MVCComponent
        implements BootableExtensionInterface
    {
        public function boot(ContainerInterface $container): void;
    }
```

---

**Revision Note (2026-01-27):** Initial plan created based on requirements analysis. Includes frontend+backend structure, color-coded events by group, and event linking to CBGroupJive pages.

**Revision Note (2026-01-27):** Added Milestone 9 for packaging and distribution. Includes Makefile for automated ZIP packaging with versioned filenames in `installation/` folder, and `yscbcalendar.update.xml` for Joomla update server support. Pattern based on existing modules `mod_ystides` and `mod_yscbsubs_expiredlist`.

**Revision Note (2026-01-28):** Implemented improvement 3 to align calendar event selection with CBGroupJive "All Events" access rules, and extended group legend color sources to `groups/allgroups` visibility.

**Revision Note (2026-01-28):** Added improvement 4 to move navigation arrows to the left and right of the calendar title, per request.

**Revision Note (2026-01-28):** Implemented improvement 4 by updating the header markup and styles to flank the title with navigation arrows.

**Revision Note (2026-01-28):** Implemented improvement 5 to treat empty event end dates as start dates in filtering and hydration.

**Revision Note (2026-01-28):** Implemented improvement 6 to avoid MySQL strict mode errors in the event range filter.

**Revision Note (2026-01-28):** Implemented improvement 7 to tighten the mobile header layout with icon buttons.

**Revision Note (2026-01-28):** Refined mobile header layout to keep controls on a single line down to ~480px and only wrap below that.
