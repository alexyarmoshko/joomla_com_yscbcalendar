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

        $query = $db->getQuery(true)
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
            ->from($db->quoteName('#__groupjive_plugin_events', 'e'))
            ->innerJoin(
                $db->quoteName('#__groupjive_groups', 'g') . ' ON ' .
                $db->quoteName('e.group') . ' = ' . $db->quoteName('g.id')
            )
            ->innerJoin(
                $db->quoteName('#__groupjive_users', 'u') . ' ON ' .
                $db->quoteName('g.id') . ' = ' . $db->quoteName('u.group')
            )
            ->where($db->quoteName('u.user_id') . ' = :userId')
            ->where($db->quoteName('u.status') . ' >= 1')
            ->where($db->quoteName('e.published') . ' = 1')
            ->where($db->quoteName('g.published') . ' = 1')
            ->where($db->quoteName('e.start') . ' <= :endDate')
            ->where($db->quoteName('e.end') . ' >= :startDate')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':startDate', $startDate->format('Y-m-d H:i:s'))
            ->bind(':endDate', $endDate->format('Y-m-d H:i:s'))
            ->order($db->quoteName('e.start') . ' ASC');

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
            $event->start_date = new \DateTime($event->start);
            $event->end_date = new \DateTime($event->end);
        }

        return $events;
    }

    /**
     * Get all groups the current user belongs to.
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
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
            ])
            ->from($db->quoteName('#__groupjive_groups', 'g'))
            ->innerJoin(
                $db->quoteName('#__groupjive_users', 'u') . ' ON ' .
                $db->quoteName('g.id') . ' = ' . $db->quoteName('u.group')
            )
            ->where($db->quoteName('u.user_id') . ' = :userId')
            ->where($db->quoteName('u.status') . ' >= 1')
            ->where($db->quoteName('g.published') . ' = 1')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER)
            ->order($db->quoteName('g.name') . ' ASC');

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

        $query = $db->getQuery(true)
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
            ->from($db->quoteName('#__groupjive_plugin_events', 'e'))
            ->innerJoin(
                $db->quoteName('#__groupjive_groups', 'g') . ' ON ' .
                $db->quoteName('e.group') . ' = ' . $db->quoteName('g.id')
            )
            ->innerJoin(
                $db->quoteName('#__groupjive_users', 'u') . ' ON ' .
                $db->quoteName('g.id') . ' = ' . $db->quoteName('u.group')
            )
            ->where($db->quoteName('e.id') . ' = :eventId')
            ->where($db->quoteName('u.user_id') . ' = :userId')
            ->where($db->quoteName('u.status') . ' >= 1')
            ->where($db->quoteName('e.published') . ' = 1')
            ->where($db->quoteName('g.published') . ' = 1')
            ->bind(':eventId', $eventId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);

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
}
