<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\View\Calendar;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Registry\Registry;

/**
 * HTML View class for the Calendar view
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The events to display
     *
     * @var array
     */
    protected array $events = [];

    /**
     * The user's groups
     *
     * @var array
     */
    protected array $groups = [];

    /**
     * The currently displayed date
     *
     * @var \DateTime
     */
    protected \DateTime $currentDate;

    /**
     * The view mode (week or month)
     *
     * @var string
     */
    protected string $viewMode = 'week';

    /**
     * The start of the current view period
     *
     * @var \DateTime
     */
    protected \DateTime $periodStart;

    /**
     * The end of the current view period
     *
     * @var \DateTime
     */
    protected \DateTime $periodEnd;

    /**
     * Component parameters
     *
     * @var Registry
     */
    protected Registry $params;

    /**
     * Day names for headers
     *
     * @var array
     */
    protected array $dayNames = [];

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        // Check if user is logged in
        if ($user === null || $user->guest) {
            $app->enqueueMessage(Text::_('COM_YSCBCALENDAR_LOGIN_REQUIRED'), 'warning');
            $this->setLayout('login');
            parent::display($tpl);
            return;
        }

        /** @var \Joomla\Component\YSCBCalendar\Site\Model\CalendarModel $model */
        $model = $this->getModel();
        $this->params = $model->getParams();

        // Get view mode from request or config default
        $this->viewMode = $app->getInput()->get('view_mode', $this->params->get('default_view', 'week'), 'string');
        if (!in_array($this->viewMode, ['week', 'month'])) {
            $this->viewMode = 'week';
        }

        // Get the target date from request or use today
        $dateParam = $app->getInput()->get('date', '', 'string');
        if (!empty($dateParam)) {
            try {
                $this->currentDate = new \DateTime($dateParam);
            } catch (\Exception $e) {
                $this->currentDate = new \DateTime();
            }
        } else {
            $this->currentDate = new \DateTime();
        }

        // Calculate period boundaries
        $this->calculatePeriodBoundaries();

        // Get events for the current period
        $this->events = $model->getEvents($this->periodStart, $this->periodEnd);

        // Get user's groups for the legend
        $this->groups = $model->getUserGroups();

        // Build day names array
        $this->buildDayNames();

        // Add assets
        $this->addAssets();

        parent::display($tpl);
    }

    /**
     * Calculate the start and end dates for the current view period.
     *
     * @return  void
     */
    protected function calculatePeriodBoundaries(): void
    {
        $weekStart = (int) $this->params->get('week_start', 1); // 0 = Sunday, 1 = Monday

        if ($this->viewMode === 'week') {
            // Calculate start of week
            $dayOfWeek = (int) $this->currentDate->format('w'); // 0 = Sunday, 6 = Saturday

            if ($weekStart === 1) {
                // Monday start: convert to Monday-based (0 = Monday, 6 = Sunday)
                $dayOfWeek = ($dayOfWeek + 6) % 7;
            }

            $this->periodStart = clone $this->currentDate;
            $this->periodStart->modify("-{$dayOfWeek} days");
            $this->periodStart->setTime(0, 0, 0);

            $this->periodEnd = clone $this->periodStart;
            $this->periodEnd->modify('+6 days');
            $this->periodEnd->setTime(23, 59, 59);
        } else {
            // Month view
            $this->periodStart = new \DateTime($this->currentDate->format('Y-m-01'));
            $this->periodStart->setTime(0, 0, 0);

            $this->periodEnd = new \DateTime($this->currentDate->format('Y-m-t'));
            $this->periodEnd->setTime(23, 59, 59);

            // Extend to include days from previous/next month to fill the grid
            $startDayOfWeek = (int) $this->periodStart->format('w');
            if ($weekStart === 1) {
                $startDayOfWeek = ($startDayOfWeek + 6) % 7;
            }
            if ($startDayOfWeek > 0) {
                $this->periodStart->modify("-{$startDayOfWeek} days");
            }

            $endDayOfWeek = (int) $this->periodEnd->format('w');
            if ($weekStart === 1) {
                $endDayOfWeek = ($endDayOfWeek + 6) % 7;
            }
            $daysToAdd = 6 - $endDayOfWeek;
            if ($daysToAdd > 0) {
                $this->periodEnd->modify("+{$daysToAdd} days");
            }
        }
    }

    /**
     * Build the array of day names for headers.
     *
     * @return  void
     */
    protected function buildDayNames(): void
    {
        $weekStart = (int) $this->params->get('week_start', 1);

        $days = [
            0 => Text::_('COM_YSCBCALENDAR_SUNDAY_SHORT'),
            1 => Text::_('COM_YSCBCALENDAR_MONDAY_SHORT'),
            2 => Text::_('COM_YSCBCALENDAR_TUESDAY_SHORT'),
            3 => Text::_('COM_YSCBCALENDAR_WEDNESDAY_SHORT'),
            4 => Text::_('COM_YSCBCALENDAR_THURSDAY_SHORT'),
            5 => Text::_('COM_YSCBCALENDAR_FRIDAY_SHORT'),
            6 => Text::_('COM_YSCBCALENDAR_SATURDAY_SHORT'),
        ];

        $this->dayNames = [];
        for ($i = 0; $i < 7; $i++) {
            $dayIndex = ($weekStart + $i) % 7;
            $this->dayNames[] = $days[$dayIndex];
        }
    }

    /**
     * Add CSS and JavaScript assets.
     *
     * @return  void
     */
    protected function addAssets(): void
    {
        /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        $wa->registerAndUseStyle(
            'com_yscbcalendar.calendar',
            'com_yscbcalendar/calendar.css',
            ['version' => 'auto']
        );

        $wa->registerAndUseScript(
            'com_yscbcalendar.calendar',
            'com_yscbcalendar/calendar.js',
            ['version' => 'auto'],
            ['defer' => true]
        );
    }

    /**
     * Get events for a specific date.
     *
     * @param   \DateTime  $date  The date to get events for
     *
     * @return  array  Events occurring on the given date
     */
    public function getEventsForDate(\DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $result = [];

        foreach ($this->events as $event) {
            $eventStart = $event->start_date->format('Y-m-d');
            $eventEnd = $event->end_date->format('Y-m-d');

            if ($dateStr >= $eventStart && $dateStr <= $eventEnd) {
                $result[] = $event;
            }
        }

        return $result;
    }

    /**
     * Format a time according to component settings.
     *
     * @param   \DateTime  $datetime  The datetime to format
     *
     * @return  string  Formatted time string
     */
    public function formatTime(\DateTime $datetime): string
    {
        $format = $this->params->get('time_format', '24') === '12' ? 'g:i A' : 'H:i';
        return $datetime->format($format);
    }

    /**
     * Check if a date is today.
     *
     * @param   \DateTime  $date  The date to check
     *
     * @return  bool
     */
    public function isToday(\DateTime $date): bool
    {
        $today = new \DateTime();
        return $date->format('Y-m-d') === $today->format('Y-m-d');
    }

    /**
     * Check if a date is in the current month.
     *
     * @param   \DateTime  $date  The date to check
     *
     * @return  bool
     */
    public function isCurrentMonth(\DateTime $date): bool
    {
        return $date->format('Y-m') === $this->currentDate->format('Y-m');
    }

    /**
     * Get navigation URL for previous period.
     *
     * @return  string
     */
    public function getPrevUrl(): string
    {
        $prevDate = clone $this->currentDate;
        if ($this->viewMode === 'week') {
            $prevDate->modify('-1 week');
        } else {
            $prevDate->modify('-1 month');
        }

        return $this->buildNavigationUrl($prevDate);
    }

    /**
     * Get navigation URL for next period.
     *
     * @return  string
     */
    public function getNextUrl(): string
    {
        $nextDate = clone $this->currentDate;
        if ($this->viewMode === 'week') {
            $nextDate->modify('+1 week');
        } else {
            $nextDate->modify('+1 month');
        }

        return $this->buildNavigationUrl($nextDate);
    }

    /**
     * Get navigation URL for today.
     *
     * @return  string
     */
    public function getTodayUrl(): string
    {
        return $this->buildNavigationUrl(new \DateTime());
    }

    /**
     * Get URL to switch view mode.
     *
     * @param   string  $mode  The view mode to switch to
     *
     * @return  string
     */
    public function getViewModeUrl(string $mode): string
    {
        return $this->buildNavigationUrl($this->currentDate, $mode);
    }

    /**
     * Build a navigation URL.
     *
     * @param   \DateTime  $date      The date to navigate to
     * @param   string     $viewMode  Optional view mode override
     *
     * @return  string
     */
    protected function buildNavigationUrl(\DateTime $date, string $viewMode = ''): string
    {
        $uri = \Joomla\CMS\Uri\Uri::getInstance();
        $uri->setVar('date', $date->format('Y-m-d'));
        $uri->setVar('view_mode', $viewMode ?: $this->viewMode);

        return $uri->toString();
    }

    /**
     * Get the period title (e.g., "January 2026" or "Jan 20 - Jan 26, 2026").
     *
     * @return  string
     */
    public function getPeriodTitle(): string
    {
        if ($this->viewMode === 'month') {
            return $this->currentDate->format('F Y');
        }

        // Week view title
        $startMonth = $this->periodStart->format('M');
        $endMonth = $this->periodEnd->format('M');
        $startDay = $this->periodStart->format('j');
        $endDay = $this->periodEnd->format('j');
        $year = $this->periodEnd->format('Y');

        if ($startMonth === $endMonth) {
            return "{$startMonth} {$startDay} - {$endDay}, {$year}";
        }

        return "{$startMonth} {$startDay} - {$endMonth} {$endDay}, {$year}";
    }
}
