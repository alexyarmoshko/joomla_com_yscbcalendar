<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\YSCBCalendar\Site\View\Calendar\HtmlView $this */
?>
<div class="com-yscbc" id="yscbcalendar">
    <div class="yscbc-header">
        <div class="yscbc-nav">
            <a href="<?php echo $this->escape($this->getTodayUrl()); ?>" class="btn btn-outline-primary btn-sm yscbc-today">
                <?php echo Text::_('COM_YSCBCALENDAR_TODAY'); ?>
            </a>
            <a href="<?php echo $this->escape($this->getPrevUrl()); ?>" class="btn btn-outline-secondary btn-sm yscbc-prev" aria-label="<?php echo Text::_('COM_YSCBCALENDAR_PREVIOUS'); ?>">
                <span aria-hidden="true">&lsaquo;</span>
            </a>
            <a href="<?php echo $this->escape($this->getNextUrl()); ?>" class="btn btn-outline-secondary btn-sm yscbc-next" aria-label="<?php echo Text::_('COM_YSCBCALENDAR_NEXT'); ?>">
                <span aria-hidden="true">&rsaquo;</span>
            </a>
        </div>

        <h2 class="yscbc-title"><?php echo $this->escape($this->getPeriodTitle()); ?></h2>

        <div class="yscbc-view-toggle btn-group" role="group" aria-label="<?php echo Text::_('COM_YSCBCALENDAR_VIEW_MODE'); ?>">
            <a href="<?php echo $this->escape($this->getViewModeUrl('week')); ?>"
               class="btn btn-sm <?php echo $this->viewMode === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo Text::_('COM_YSCBCALENDAR_VIEW_WEEK'); ?>
            </a>
            <a href="<?php echo $this->escape($this->getViewModeUrl('month')); ?>"
               class="btn btn-sm <?php echo $this->viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo Text::_('COM_YSCBCALENDAR_VIEW_MONTH'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->viewMode === 'week') : ?>
        <?php echo $this->loadTemplate('week'); ?>
    <?php else : ?>
        <?php echo $this->loadTemplate('month'); ?>
    <?php endif; ?>

    <?php if (!empty($this->groups)) : ?>
    <div class="yscbc-legend">
        <h4><?php echo Text::_('COM_YSCBCALENDAR_LEGEND'); ?></h4>
        <ul class="yscbc-legend-list">
            <?php foreach ($this->groups as $group) : ?>
            <li>
                <span class="yscbc-legend-color" style="background-color: <?php echo $this->escape($group->color); ?>;"></span>
                <span class="yscbc-legend-name"><?php echo $this->escape($group->name); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
