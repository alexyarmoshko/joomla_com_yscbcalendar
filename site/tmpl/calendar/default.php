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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                            <span class="yscbc-modal-date-text"></span>
                        </div>
                        <div class="yscbc-modal-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            <span class="yscbc-modal-time-text"></span>
                        </div>
                        <div class="yscbc-modal-location" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                            </svg>
                            <span class="yscbc-modal-location-text"></span>
                        </div>
                        <div class="yscbc-modal-group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                            </svg>
                            <span class="yscbc-modal-group-text"></span>
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
