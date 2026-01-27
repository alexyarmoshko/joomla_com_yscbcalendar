<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * ComponentDispatcher class for com_yscbcalendar
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the default view to the calendar.
     *
     * @return  void
     */
    public function dispatch(): void
    {
        parent::dispatch();
    }
}
