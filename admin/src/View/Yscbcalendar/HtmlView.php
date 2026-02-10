<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Administrator\View\Yscbcalendar;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
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
     * Component version from the installed manifest.
     *
     * @var string
     */
    protected string $version = '';

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
        $this->version = $this->getComponentVersion();

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

    /**
     * Read the component version from the installed manifest cache.
     *
     * @return  string  The version string, or empty if unavailable
     */
    protected function getComponentVersion(): string
    {
        $component = ComponentHelper::getComponent('com_yscbcalendar');
        $cache = $component->manifest_cache ?? '';

        if ($cache === '') {
            return '';
        }

        $manifest = json_decode($cache);

        return $manifest->version ?? '';
    }
}
