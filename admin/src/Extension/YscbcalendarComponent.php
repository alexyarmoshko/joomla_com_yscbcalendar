<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_yscbcalendar
 */
class YSCBCalendarComponent extends MVCComponent implements BootableExtensionInterface
{
    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     */
    public function boot(ContainerInterface $container): void
    {
        // Nothing to boot for this component
    }
}
