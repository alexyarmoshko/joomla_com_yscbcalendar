<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\YSCBCalendar\Site\Service\GroupJiveGateway;
use Joomla\Database\DatabaseInterface;

/**
 * Calendar Model for YakShaver CB Calendar
 *
 * Retrieves events from CBGroupJive for groups the current user belongs to.
 */
class CalendarModel extends BaseDatabaseModel
{
    /**
     * CBGroupJive group type value for secret (hidden) groups.
     */
    private const GROUP_TYPE_SECRET = 3;

    /**
     * Color palette for groups
     *
     * @var array
     */
    protected array $colorPalette = [
        '#039be5',
        '#7986cb',
        '#33b679',
        '#8e24aa',
        '#e67c73',
        '#f6bf26',
        '#f4511e',
        '#0b8043',
        '#616161',
        '#3f51b5',
    ];

    /**
     * Get events for the current user within a date range.
     *
     * @param   \DateTimeInterface  $startDate  Start of the date range
     * @param   \DateTimeInterface  $endDate    End of the date range
     *
     * @return  array  Array of event objects
     */
    public function getEvents(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user === null || $user->guest) {
            return [];
        }

        $userId = (int) $user->id;
        $db = $this->getDatabase();
        $isModerator = $this->isModerator($userId);

        if (!(new GroupJiveGateway())->canAccessEvents($userId)) {
            return [];
        }

        $startField = $db->quoteName('e.start');
        $endField = $db->quoteName('e.end');
        $normalizedEndField = 'NULLIF(' . $endField . ", '0000-00-00 00:00:00')";
        $effectiveEndField = 'COALESCE(' . $normalizedEndField . ', ' . $startField . ')';
        $startDateString = $startDate->format('Y-m-d H:i:s');
        $endDateString = $endDate->format('Y-m-d H:i:s');

        $query = $this->buildBaseEventQuery($userId, $isModerator)
            ->select([
                $db->quoteName('e.id'),
                $db->quoteName('e.title'),
                $db->quoteName('e.event', 'description'),
                $db->quoteName('e.location'),
                $db->quoteName('e.address'),
                $db->quoteName('e.start'),
                $db->quoteName('e.end'),
                $db->quoteName('g.id', 'group_id'),
                $db->quoteName('g.name', 'group_name'),
            ])
            ->where($startField . ' <= :endDate')
            ->where($effectiveEndField . ' >= :startDate')
            ->bind(':startDate', $startDateString)
            ->bind(':endDate', $endDateString)
            ->order($startField . ' ASC');

        $db->setQuery($query);

        try {
            $events = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }

        // Add color and URL to each event
        foreach ($events as $event) {
            $event->color = $this->generateGroupColor((int) $event->group_id);
            $event->url = $this->buildEventUrl((int) $event->group_id);
            $event->group_url = $this->buildGroupUrl((int) $event->group_id);
            $this->hydrateEventDates($event);
        }

        return $events;
    }

    /**
     * Generate a consistent color for a group based on its ID.
     *
     * @param   int  $groupId  The group ID
     *
     * @return  string  Hex color code
     */
    public function generateGroupColor(int $groupId): string
    {
        return $this->colorPalette[$groupId % count($this->colorPalette)];
    }

    /**
     * Build the URL to view an event in CBGroupJive.
     *
     * @param   int  $groupId  The group ID
     *
     * @return  string  The event URL
     */
    protected function buildEventUrl(int $groupId): string
    {
        return (new GroupJiveGateway())->groupEventsUrl($groupId);
    }

    /**
     * Build the URL to view a group in CBGroupJive.
     *
     * @param   int  $groupId  The group ID
     *
     * @return  string  The group URL
     */
    protected function buildGroupUrl(int $groupId): string
    {
        return (new GroupJiveGateway())->groupUrl($groupId);
    }

    /**
     * Get a single event by ID for the current user.
     *
     * @param   int  $eventId  The event ID
     *
     * @return  object|null  The event object or null if not found/unauthorized
     */
    public function getEvent(int $eventId): ?object
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user === null || $user->guest) {
            return null;
        }

        $userId = (int) $user->id;
        $db = $this->getDatabase();
        $isModerator = $this->isModerator($userId);

        if (!(new GroupJiveGateway())->canAccessEvents($userId)) {
            return null;
        }

        $query = $this->buildBaseEventQuery($userId, $isModerator)
            ->select([
                $db->quoteName('e.id'),
                $db->quoteName('e.title'),
                $db->quoteName('e.event', 'description'),
                $db->quoteName('e.location'),
                $db->quoteName('e.address'),
                $db->quoteName('e.start'),
                $db->quoteName('e.end'),
                $db->quoteName('g.id', 'group_id'),
                $db->quoteName('g.name', 'group_name'),
                $db->quoteName('e.user_id', 'owner_id'),
            ])
            ->where($db->quoteName('e.id') . ' = :eventId')
            ->bind(':eventId', $eventId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            $event = $db->loadObject();
        } catch (\RuntimeException $e) {
            return null;
        }

        if ($event === null) {
            return null;
        }

        // Add color and URL to the event
        $event->color = $this->generateGroupColor((int) $event->group_id);
        $event->url = $this->buildEventUrl((int) $event->group_id);
        $event->group_url = $this->buildGroupUrl((int) $event->group_id);
        $event->owner_name = $this->resolveOwnerName($event);
        $event->owner_url = $this->buildProfileUrl((int) $event->owner_id);
        $this->hydrateEventDates($event);

        return $event;
    }

    /**
     * Resolve the Community Builder display name for an event owner.
     *
     * @param   object  $event  The event object
     *
     * @return  string  The owner display name, or empty if unavailable
     */
    protected function resolveOwnerName(object $event): string
    {
        return (new GroupJiveGateway())->formatOwnerName((int) $event->owner_id);
    }

    /**
     * Build the URL to view a user's CB profile.
     *
     * Routed with $xhtml = false: this URL is delivered as JSON and assigned to a
     * DOM `href` property, which performs no entity decoding. The default `true`
     * would emit `&amp;` and corrupt any query string that survives routing.
     *
     * @param   int  $userId  The user ID
     *
     * @return  string  The profile URL, or empty for an unknown user
     */
    protected function buildProfileUrl(int $userId): string
    {
        return (new GroupJiveGateway())->userProfileUrl($userId);
    }

    /**
     * Get component parameters.
     *
     * @return  \Joomla\Registry\Registry
     */
    public function getParams(): \Joomla\Registry\Registry
    {
        return ComponentHelper::getParams('com_yscbcalendar');
    }

    /**
     * Ensure event date values are hydrated with a fallback for empty end values.
     *
     * @param   object  $event  The event object to hydrate
     *
     * @return  void
     */
    protected function hydrateEventDates(object $event): void
    {
        $event->start_date = new \DateTime($event->start);
        $event->end_date = $this->resolveEventEndDate($event->end ?? '', $event->start_date);
    }

    /**
     * Resolve the event end date, falling back to the start date when missing.
     *
     * @param   string     $endValue   Raw end date value from storage
     * @param   \DateTime  $startDate  Parsed start date
     *
     * @return  \DateTime
     */
    protected function resolveEventEndDate(string $endValue, \DateTime $startDate): \DateTime
    {
        $endValue = trim($endValue);

        if ($endValue === '' || $endValue === '0000-00-00 00:00:00') {
            return clone $startDate;
        }

        return new \DateTime($endValue);
    }

    /**
     * Build the base event query with CBGroupJive "All Events" access rules.
     *
     * @param   int   $userId       The current user ID
     * @param   bool  $isModerator  Whether the user is a CBGroupJive moderator
     *
     * @return  \Joomla\Database\DatabaseQuery
     */
    protected function buildBaseEventQuery(int $userId, bool $isModerator): \Joomla\Database\DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->from($db->quoteName('#__groupjive_plugin_events', 'e'))
            ->leftJoin(
                $db->quoteName('#__groupjive_groups', 'g') . ' ON ' .
                $db->quoteName('g.id') . ' = ' . $db->quoteName('e.group')
            )
            ->leftJoin(
                $db->quoteName('#__groupjive_categories', 'c') . ' ON ' .
                $db->quoteName('c.id') . ' = ' . $db->quoteName('g.category')
            )
            ->leftJoin(
                $db->quoteName('#__comprofiler', 'cb') . ' ON ' .
                $db->quoteName('cb.id') . ' = ' . $db->quoteName('e.user_id')
            )
            ->leftJoin(
                $db->quoteName('#__users', 'j') . ' ON ' .
                $db->quoteName('j.id') . ' = ' . $db->quoteName('e.user_id')
            );

        if (!$isModerator && $userId > 0) {
            $query->leftJoin(
                $db->quoteName('#__groupjive_users', 'u') . ' ON ' .
                $db->quoteName('u.user_id') . ' = :userId' .
                ' AND ' . $db->quoteName('u.group') . ' = ' . $db->quoteName('g.id') .
                ' AND ' . $db->quoteName('u.status') . ' BETWEEN 0 AND 3'
            );
        }

        $this->applyEventAccessFilters($query, $userId, $isModerator);

        if (!$isModerator) {
            $query->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);
        }

        return $query;
    }

    /**
     * Apply access rules from CBGroupJive "All Events" view to the query.
     *
     * @param   \Joomla\Database\DatabaseQuery  $query        The query to update
     * @param   int                            $userId       The current user ID
     * @param   bool                           $isModerator  Whether the user is a moderator
     *
     * @return  void
     */
    protected function applyEventAccessFilters(\Joomla\Database\DatabaseQuery $query, int $userId, bool $isModerator): void
    {
        $db = $this->getDatabase();

        $query->where($db->quoteName('cb.approved') . ' = 1')
            ->where($db->quoteName('cb.confirmed') . ' = 1')
            ->where($db->quoteName('j.block') . ' = 0');

        if ($isModerator) {
            return;
        }

        $query->where(
            '(' . $db->quoteName('e.user_id') . ' = :userId' .
            ' OR ' . $db->quoteName('e.published') . ' = 1)'
        );

        if ($userId > 0) {
            $query->where(
                '(' . $db->quoteName('g.user_id') . ' = :userId' .
                ' OR (' . $db->quoteName('g.published') . ' = 1' .
                ' AND (' . $db->quoteName('g.type') . ' != ' . self::GROUP_TYPE_SECRET .
                ' OR ' . $db->quoteName('u.id') . ' IS NOT NULL)))'
            );
        } else {
            $query->where($db->quoteName('g.published') . ' = 1')
                ->where($db->quoteName('g.type') . ' != ' . self::GROUP_TYPE_SECRET);
        }

        $accessLevels = $this->getAccessLevels();
        $accessPlaceholders = $query->bindArray($accessLevels, \Joomla\Database\ParameterType::INTEGER);
        $accessIn = implode(',', $accessPlaceholders);
        $categoryClause = '(' . $db->quoteName('c.published') . ' = 1' .
            ' AND ' . $db->quoteName('c.access') . ' IN (' . $accessIn . '))';

        if ($this->allowUncategorizedGroups()) {
            $categoryClause = '(' . $categoryClause .
                ' OR ' . $db->quoteName('g.category') . ' = 0)';
        }

        $query->where($categoryClause);
    }

    /**
     * Get the view access levels for the current user.
     *
     * @return  array
     */
    protected function getAccessLevels(): array
    {
        $user = Factory::getApplication()->getIdentity();

        return (new GroupJiveGateway())->getAccessLevels($user ? (int) $user->id : 0);
    }

    /**
     * Determine if the current user is a CBGroupJive moderator.
     *
     * @param   int  $userId  The current user ID
     *
     * @return  bool
     */
    protected function isModerator(int $userId): bool
    {
        return (new GroupJiveGateway())->isModerator($userId);
    }

    /**
     * Check whether uncategorized groups should be included.
     *
     * @return  bool
     */
    protected function allowUncategorizedGroups(): bool
    {
        return (new GroupJiveGateway())->allowsUncategorizedGroups();
    }
}
