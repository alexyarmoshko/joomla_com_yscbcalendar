<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Component\YSCBCalendar\Site\Model\CalendarModel;

/**
 * Event Controller for AJAX event detail requests
 */
class EventController extends BaseController
{
    /**
     * Get event details via AJAX.
     *
     * @return  void
     */
    public function getEvent(): void
    {
        // Check for valid CSRF token
        if (!Session::checkToken('get')) {
            $this->sendJsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app = Factory::getApplication();
        $eventId = $app->getInput()->getInt('id', 0);

        if ($eventId <= 0) {
            $this->sendJsonResponse(null, Text::_('COM_YSCBCALENDAR_EVENT_NOT_FOUND'), true);
            return;
        }

        /** @var CalendarModel $model */
        $model = $this->getModel('Calendar', 'Site');

        if ($model === null) {
            $this->sendJsonResponse(null, Text::_('COM_YSCBCALENDAR_ERROR_MODEL'), true);
            return;
        }

        $event = $model->getEvent($eventId);

        if ($event === null) {
            $this->sendJsonResponse(null, Text::_('COM_YSCBCALENDAR_EVENT_NOT_FOUND'), true);
            return;
        }

        // Prepare event data for JSON response
        $params = $model->getParams();
        $timeFormat = $params->get('time_format', '24') === '12' ? 'g:i A' : 'H:i';
        $dateFormat = 'l, F j, Y';

        $eventData = [
            'id'          => (int) $event->id,
            'title'       => $event->title,
            'description' => $event->description,
            'location'    => $event->location,
            'address'     => $event->address,
            'start_date'  => $event->start_date->format($dateFormat),
            'start_time'  => $event->start_date->format($timeFormat),
            'end_date'    => $event->end_date->format($dateFormat),
            'end_time'    => $event->end_date->format($timeFormat),
            'group_id'    => (int) $event->group_id,
            'group_name'  => $event->group_name,
            'color'       => $event->color,
            'url'         => $event->url,
            'same_day'    => $event->start_date->format('Y-m-d') === $event->end_date->format('Y-m-d'),
        ];

        $this->sendJsonResponse($eventData);
    }

    /**
     * Send a JSON response and close the application.
     *
     * @param   mixed   $data     The response data
     * @param   string  $message  Optional message
     * @param   bool    $error    Whether this is an error response
     *
     * @return  void
     */
    protected function sendJsonResponse($data, string $message = '', bool $error = false): void
    {
        $app = Factory::getApplication();

        $app->setHeader('Content-Type', 'application/json; charset=utf-8');

        $response = new JsonResponse($data, $message, $error);
        echo $response;

        $app->close();
    }
}
