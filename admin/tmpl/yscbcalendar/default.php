<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\YSCBCalendar\Administrator\View\YSCBCalendar\HtmlView $this */
?>
<div class="com-yscbc-dashboard">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_YSCBCALENDAR'); ?></h3>
                </div>
                <div class="card-body">
                    <p><?php echo Text::_('COM_YSCBCALENDAR_DASHBOARD_DESC'); ?></p>
                    <ul>
                        <li><?php echo Text::_('COM_YSCBCALENDAR_FEATURE_WEEK_VIEW'); ?></li>
                        <li><?php echo Text::_('COM_YSCBCALENDAR_FEATURE_MONTH_VIEW'); ?></li>
                        <li><?php echo Text::_('COM_YSCBCALENDAR_FEATURE_COLOR_CODED'); ?></li>
                        <li><?php echo Text::_('COM_YSCBCALENDAR_FEATURE_NAVIGATION'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_YSCBCALENDAR_QUICK_INFO'); ?></h3>
                </div>
                <div class="card-body">
                    <dl>
                        <dt><?php echo Text::_('COM_YSCBCALENDAR_VERSION'); ?></dt>
                        <dd>1.0.0</dd>
                        <dt><?php echo Text::_('COM_YSCBCALENDAR_AUTHOR'); ?></dt>
                        <dd>Yak Shaver</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
