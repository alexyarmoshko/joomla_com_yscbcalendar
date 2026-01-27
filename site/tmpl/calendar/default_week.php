<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\YSCBCalendar\Site\View\Calendar\HtmlView $this */

$currentDay = clone $this->periodStart;
?>
<div class="yscbc-week">
    <div class="yscbc-grid">
        <div class="yscbc-grid-header">
            <?php foreach ($this->dayNames as $dayName) : ?>
            <div class="yscbc-grid-header-cell"><?php echo $this->escape($dayName); ?></div>
            <?php endforeach; ?>
        </div>

        <div class="yscbc-grid-dates">
            <?php for ($i = 0; $i < 7; $i++) : ?>
                <?php
                $date = clone $this->periodStart;
                $date->modify("+{$i} days");
                $isToday = $this->isToday($date);
                ?>
            <div class="yscbc-grid-date-cell <?php echo $isToday ? 'is-today' : ''; ?>">
                <span class="yscbc-date-number <?php echo $isToday ? 'is-today' : ''; ?>"><?php echo $date->format('j'); ?></span>
            </div>
            <?php endfor; ?>
        </div>

        <div class="yscbc-grid-body">
            <?php for ($i = 0; $i < 7; $i++) : ?>
                <?php
                $date = clone $this->periodStart;
                $date->modify("+{$i} days");
                $dayEvents = $this->getEventsForDate($date);
                $isToday = $this->isToday($date);
                ?>
            <div class="yscbc-grid-cell <?php echo $isToday ? 'is-today' : ''; ?>">
                <?php if (empty($dayEvents)) : ?>
                    <div class="yscbc-no-events">&nbsp;</div>
                <?php else : ?>
                    <?php foreach ($dayEvents as $event) : ?>
                    <a href="#"
                       class="yscbc-event"
                       style="background-color: <?php echo $this->escape($event->color); ?>;"
                       title="<?php echo $this->escape($event->title); ?>"
                       data-event-id="<?php echo (int) $event->id; ?>"
                       data-event-url="<?php echo $this->escape($event->url); ?>">
                        <span class="yscbc-event-time"><?php echo $this->formatTime($event->start_date); ?></span>
                        <span class="yscbc-event-title"><?php echo $this->escape($event->title); ?></span>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
