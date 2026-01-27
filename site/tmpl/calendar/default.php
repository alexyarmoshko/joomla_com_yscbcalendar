<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\YSCBCalendar\Site\View\Calendar\HtmlView $this */

// Build AJAX URL for event details
$ajaxUrl = Uri::base() . 'index.php?option=com_yscbcalendar&task=event.getEvent&format=json&' . Session::getFormToken() . '=1';
?>
<div class="com-yscbc" id="yscbcalendar" data-ajax-url="<?php echo $this->escape($ajaxUrl); ?>">
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

<!-- Event Modal -->
<div class="modal fade" id="yscbcEventModal" tabindex="-1" aria-labelledby="yscbcEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header yscbc-modal-header">
                <h5 class="modal-title" id="yscbcEventModalLabel"><?php echo Text::_('COM_YSCBCALENDAR_LOADING'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
            </div>
            <div class="modal-body yscbc-modal-body">
                <div class="yscbc-modal-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php echo Text::_('COM_YSCBCALENDAR_LOADING'); ?></span>
                    </div>
                </div>
                <div class="yscbc-modal-content" style="display: none;">
                    <div class="yscbc-modal-color-bar"></div>
                    <div class="yscbc-modal-meta">
                        <div class="yscbc-modal-datetime">
                            <span class="fa fa-calendar-o text-center gjGroupEventIcon"></span>
                            <span class="yscbc-modal-date-text"></span>
                        </div>
                        <div class="yscbc-modal-time">
                            <span class="fa fa-clock-o text-center gjGroupEventIcon"></span>
                            <span class="yscbc-modal-time-text"></span>
                        </div>
                        <div class="yscbc-modal-location" style="display: none;">
                            <span class="fa fa-map-marker text-center gjGroupEventIcon"></span>
                            <span class="yscbc-modal-location-text"></span>
                        </div>
                        <div class="yscbc-modal-group">
                            <span class="fa fa-home text-center gjGroupEventIcon"></span>
                            <a class="yscbc-modal-group-link" href="#" target="_parent" rel="noopener">
                                <span class="yscbc-modal-group-text"></span>
                            </a>
                        </div>
                    </div>
                    <div class="yscbc-modal-description"></div>
                </div>
                <div class="yscbc-modal-error" style="display: none;">
                    <div class="alert alert-danger" role="alert">
                        <span class="yscbc-modal-error-text"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Text::_('JCLOSE'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
