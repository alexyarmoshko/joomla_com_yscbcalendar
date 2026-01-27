<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\YSCBCalendar\Site\View\Calendar\HtmlView $this */
?>
<div class="com-yscbc-login">
    <div class="alert alert-info">
        <h4 class="alert-heading"><?php echo Text::_('COM_YSCBCALENDAR_LOGIN_REQUIRED_TITLE'); ?></h4>
        <p><?php echo Text::_('COM_YSCBCALENDAR_LOGIN_REQUIRED_DESC'); ?></p>
        <hr>
        <a href="<?php echo Route::_('index.php?option=com_users&view=login'); ?>" class="btn btn-primary">
            <?php echo Text::_('COM_YSCBCALENDAR_LOGIN_BUTTON'); ?>
        </a>
    </div>
</div>
