<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\YSCBCalendar\Site\View\Calendar\HtmlView $this */

// Calculate number of weeks to display
$dayCount = $this->periodStart->diff($this->periodEnd)->days + 1;
$weeks = (int) ceil($dayCount / 7);
?>
<div class="yscbc-month">
    <div class="yscbc-grid yscbc-grid-month">
        <div class="yscbc-grid-header">
            <?php foreach ($this->dayNames as $dayName) : ?>
            <div class="yscbc-grid-header-cell"><?php echo $this->escape($dayName); ?></div>
            <?php endforeach; ?>
        </div>

        <div class="yscbc-grid-body-month">
            <?php
            $currentDay = clone $this->periodStart;
            for ($week = 0; $week < $weeks; $week++) :
            ?>
            <div class="yscbc-grid-row">
                <?php for ($day = 0; $day < 7; $day++) : ?>
                    <?php
                    $isToday = $this->isToday($currentDay);
                    $isCurrentMonth = $this->isCurrentMonth($currentDay);
                    $dayEvents = $this->getEventsForDate($currentDay);
                    $cellClasses = ['yscbc-grid-cell-month'];
                    if ($isToday) {
                        $cellClasses[] = 'is-today';
                    }
                    if (!$isCurrentMonth) {
                        $cellClasses[] = 'is-other-month';
                    }
                    ?>
                <div class="<?php echo implode(' ', $cellClasses); ?>">
                    <div class="yscbc-cell-header">
                        <span class="yscbc-date-number <?php echo $isToday ? 'is-today' : ''; ?>"><?php echo $currentDay->format('j'); ?></span>
                    </div>
                    <div class="yscbc-cell-events">
                        <?php foreach (array_slice($dayEvents, 0, 3) as $event) : ?>
                        <a href="<?php echo $this->escape($event->url); ?>"
                           class="yscbc-event yscbc-event-month"
                           style="background-color: <?php echo $this->escape($event->color); ?>;"
                           title="<?php echo $this->escape($event->title); ?>">
                            <span class="yscbc-event-title"><?php echo $this->escape($event->title); ?></span>
                        </a>
                        <?php endforeach; ?>
                        <?php if (count($dayEvents) > 3) : ?>
                        <div class="yscbc-more-events">
                            <?php echo Text::sprintf('COM_YSCBCALENDAR_MORE_EVENTS', count($dayEvents) - 3); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                    <?php $currentDay->modify('+1 day'); ?>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
