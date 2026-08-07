<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\YSCBCalendar\Site\Model\CalendarModel;
use Joomla\Filter\InputFilter;

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

        // Keep a Community Builder dependency failure inside the JSON contract, so the
        // modal shows the error instead of failing to parse an error page.
        try {
            $event = $model->getEvent($eventId);
        } catch (\RuntimeException $e) {
            $this->sendJsonResponse(null, $e->getMessage(), true);
            return;
        }

        if ($event === null) {
            $this->sendJsonResponse(null, Text::_('COM_YSCBCALENDAR_EVENT_NOT_FOUND'), true);
            return;
        }

        // Prepare event data for JSON response
        $params = $model->getParams();
        $timeFormat = $params->get('time_format', '24') === '12' ? 'g:i A' : 'H:i';
        $dateFormat = 'l, F j, Y';
        $now = Factory::getDate();
        $startDate = $event->start_date;
        $endDate = $event->end_date;
        $status = 0;

        if ($endDate < $now) {
            $status = 1;
        } elseif ($startDate <= $now && $endDate >= $now) {
            $status = 2;
        }

        $eventData = [
            'id' => (int) $event->id,
            'title' => $event->title,
            'description' => $this->sanitizeHtml($event->description ?? ''),
            'location' => $event->location,
            'address' => $event->address,
            'start_date' => $event->start_date->format($dateFormat),
            'start_time' => $event->start_date->format($timeFormat),
            'end_date' => $event->end_date->format($dateFormat),
            'end_time' => $event->end_date->format($timeFormat),
            'group_id' => (int) $event->group_id,
            'group_name' => $event->group_name,
            'group_url' => $event->group_url,
            'owner_name' => $event->owner_name,
            'owner_url' => $event->owner_url,
            'color' => $event->color,
            'url' => $event->url,
            'status' => $status,
            'same_day' => $event->start_date->format('Y-m-d') === $event->end_date->format('Y-m-d'),
        ];

        $this->sendJsonResponse($eventData);
    }

    /**
     * Sanitize HTML content using a whitelist of safe tags and attributes.
     *
     * Strips scripts, event handlers, and other XSS vectors while preserving
     * safe formatting tags that CBGroupJive event descriptions may contain.
     *
     * @param   string  $html  Raw HTML content
     *
     * @return  string  Sanitized HTML
     */
    protected function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $allowedTags = [
            'a',
            'b',
            'blockquote',
            'br',
            'div',
            'em',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'i',
            'img',
            'li',
            'ol',
            'p',
            'span',
            'strong',
            'table',
            'tbody',
            'td',
            'th',
            'thead',
            'tr',
            'u',
            'ul',
        ];

        $allowedAttributes = [
            'alt',
            'class',
            'height',
            'href',
            'rel',
            'src',
            'target',
            'width',
        ];

        $filter = new InputFilter(
            $allowedTags,
            $allowedAttributes,
            InputFilter::ONLY_ALLOW_DEFINED_TAGS,
            InputFilter::ONLY_ALLOW_DEFINED_ATTRIBUTES
        );

        $cleanHtml = $filter->clean($html, 'html');

        // InputFilter normalizes attributes to lowercase names with double-quoted values.
        // Keep the tag and directory case-insensitive, but re-emit the path's original case.
        return preg_replace_callback(
            '#(<img\b[^>]*\bsrc=")(images/)#i',
            static fn(array $matches): string => $matches[1] . Uri::root(true) . '/' . $matches[2],
            $cleanHtml
        ) ?? $cleanHtml;
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
