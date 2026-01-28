<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

/**
 * Calendar Model for YakShaver CB Calendar
 *
 * Retrieves events from CBGroupJive for groups the current user belongs to.
 */
class CalendarModel extends BaseDatabaseModel
{
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
            ->where($db->quoteName('e.start') . ' <= :endDate')
            ->where($db->quoteName('e.end') . ' >= :startDate')
            ->bind(':startDate', $startDate->format('Y-m-d H:i:s'))
            ->bind(':endDate', $endDate->format('Y-m-d H:i:s'))
            ->order($db->quoteName('e.start') . ' ASC');

        if (!$isModerator) {
            $query->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);
        }

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
            $event->url = $this->buildEventUrl((int) $event->id, (int) $event->group_id);
            $event->group_url = $this->buildGroupUrl((int) $event->group_id);
            $event->start_date = new \DateTime($event->start);
            $event->end_date = new \DateTime($event->end);
        }

        return $events;
    }

    /**
     * Get all groups visible in CBGroupJive "All Groups" view.
     *
     * @return  array  Array of group objects with id, name, and color
     */
    public function getUserGroups(): array
    {
        $user = Factory::getApplication()->getIdentity();

        if ($user === null || $user->guest) {
            return [];
        }

        $userId = (int) $user->id;
        $userEmail = (string) $user->email;
        $db = $this->getDatabase();
        $isModerator = $this->isModerator($userId);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
            ])
            ->from($db->quoteName('#__groupjive_groups', 'g'))
            ->leftJoin(
                $db->quoteName('#__comprofiler', 'cb') . ' ON ' .
                $db->quoteName('cb.id') . ' = ' . $db->quoteName('g.user_id')
            )
            ->leftJoin(
                $db->quoteName('#__users', 'j') . ' ON ' .
                $db->quoteName('j.id') . ' = ' . $db->quoteName('g.user_id')
            )
            ->leftJoin(
                $db->quoteName('#__groupjive_categories', 'c') . ' ON ' .
                $db->quoteName('c.id') . ' = ' . $db->quoteName('g.category')
            )
            ->order($db->quoteName('g.name') . ' ASC');

        if (!$isModerator && $userId > 0) {
            $query->leftJoin(
                $db->quoteName('#__groupjive_users', 'u') . ' ON ' .
                $db->quoteName('u.user_id') . ' = :userId' .
                ' AND ' . $db->quoteName('u.group') . ' = ' . $db->quoteName('g.id') .
                ' AND ' . $db->quoteName('u.status') . ' BETWEEN 0 AND 3'
            );
        }

        if ($userId > 0) {
            $query->leftJoin(
                $db->quoteName('#__groupjive_invites', 'i') . ' ON ' .
                $db->quoteName('i.group') . ' = ' . $db->quoteName('g.id') .
                ' AND ' . $db->quoteName('i.accepted') . ' IS NULL' .
                ' AND (' . $db->quoteName('i.email') . ' = ' . $db->quote($userEmail) .
                ' OR ' . $db->quoteName('i.user') . ' = ' . (int) $userId . ')'
            );
        }

        $query->where($db->quoteName('cb.approved') . ' = 1')
            ->where($db->quoteName('cb.confirmed') . ' = 1')
            ->where($db->quoteName('j.block') . ' = 0');

        if (!$isModerator) {
            if ($userId > 0) {
                $query->where(
                    '(' . $db->quoteName('g.user_id') . ' = :userId' .
                    ' OR (' . $db->quoteName('g.published') . ' = 1' .
                    ' AND (' . $db->quoteName('g.type') . ' != 3' .
                    ' OR ' . $db->quoteName('u.id') . ' IS NOT NULL' .
                    ' OR ' . $db->quoteName('i.id') . ' IS NOT NULL)))'
                );
            } else {
                $query->where($db->quoteName('g.published') . ' = 1')
                    ->where($db->quoteName('g.type') . ' != 3');
            }

            $accessLevels = $this->getAccessLevels();
            $accessList = implode(',', $accessLevels);
            $categoryClause = '(' . $db->quoteName('c.published') . ' = 1' .
                ' AND ' . $db->quoteName('c.access') . ' IN (' . $accessList . '))';

            if ($this->allowUncategorizedGroups()) {
                $categoryClause = '(' . $categoryClause .
                    ' OR ' . $db->quoteName('g.category') . ' = 0)';
            }

            $query->where($categoryClause);
        }

        if (!$isModerator) {
            $query->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);
        }

        $db->setQuery($query);

        try {
            $groups = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }

        // Add color to each group
        foreach ($groups as $group) {
            $group->color = $this->generateGroupColor((int) $group->id);
        }

        return $groups;
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
     * @param   int  $eventId  The event ID
     * @param   int  $groupId  The group ID
     *
     * @return  string  The event URL
     */
    protected function buildEventUrl(int $eventId, int $groupId): string
    {
        return Route::_(
            'index.php?option=com_comprofiler'
            . '&view=pluginclass'
            . '&plugin=cbgroupjiveevents'
            . '&action=events.show'
            . '&func=show'
            . '&id=' . $eventId
            . '&group=' . $groupId
        );
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
        return Route::_(
            'index.php?option=com_comprofiler'
            . '&view=pluginclass'
            . '&plugin=cbgroupjive'
            . '&action=groups'
            . '&func=show'
            . '&id=' . $groupId
        );
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
            ->where($db->quoteName('e.id') . ' = :eventId')
            ->bind(':eventId', $eventId, \Joomla\Database\ParameterType::INTEGER);

        if (!$isModerator) {
            $query->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);
        }

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
        $event->url = $this->buildEventUrl((int) $event->id, (int) $event->group_id);
        $event->group_url = $this->buildGroupUrl((int) $event->group_id);
        $event->start_date = new \DateTime($event->start);
        $event->end_date = new \DateTime($event->end);

        return $event;
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
                ' AND (' . $db->quoteName('g.type') . ' != 3' .
                ' OR ' . $db->quoteName('u.id') . ' IS NOT NULL)))'
            );
        } else {
            $query->where($db->quoteName('g.published') . ' = 1')
                ->where($db->quoteName('g.type') . ' != 3');
        }

        $accessLevels = $this->getAccessLevels();
        $accessList = implode(',', $accessLevels);
        $categoryClause = '(' . $db->quoteName('c.published') . ' = 1' .
            ' AND ' . $db->quoteName('c.access') . ' IN (' . $accessList . '))';

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
        $levels = $user ? $user->getAuthorisedViewLevels() : [];
        $levels = array_values(array_unique(array_map('intval', $levels)));

        return $levels ?: [1];
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
        if ($userId <= 0) {
            return false;
        }

        // Default to Joomla super users if CBGroupJive is not available.
        $user = Factory::getApplication()->getIdentity();
        return $user ? $user->authorise('core.admin') : false;
    }

    /**
     * Check whether uncategorized groups should be included.
     *
     * @return  bool
     */
    protected function allowUncategorizedGroups(): bool
    {
        return true;
    }
}
