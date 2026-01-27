<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Administrator\View\YSCBCalendar;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for the YakShaver CB Calendar dashboard
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_YSCBCALENDAR_DASHBOARD_TITLE'), 'calendar');
        ToolbarHelper::preferences('com_yscbcalendar');
    }
}
