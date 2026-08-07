<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Service;

defined('_JEXEC') or die;

use CB\Plugin\GroupJive\CBGroupJive;
use CB\Plugin\GroupJiveEvents\CBGroupJiveEvents;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Provides the component's boundary to Community Builder and GroupJive.
 */
class GroupJiveGateway
{
    private static bool $loaded = false;

    /**
     * Determine whether a user is a CBGroupJive moderator.
     *
     * @param   int  $userId  User ID
     *
     * @return  bool
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function isModerator(int $userId): bool
    {
        $this->load();

        return CBGroupJive::isModerator($userId);
    }

    /**
     * Load and verify the component's Community Builder dependencies.
     *
     * @return  void
     *
     * @throws  \RuntimeException
     */
    private function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $previous = null;

        try {
            $reason = $this->bootstrap();
        } catch (\Throwable $e) {
            $reason = 'Unexpected error during Community Builder bootstrap: ' . $e->getMessage();
            $previous = $e;
        }

        if ($reason !== '') {
            // The user-facing message is deliberately generic, so the reason is logged:
            // a CB database fault must not be indistinguishable from an uninstalled CB.
            Log::addLogger(['text_file' => 'com_yscbcalendar.log.php'], Log::ALL, ['com_yscbcalendar']);
            Log::add($reason, Log::ERROR, 'com_yscbcalendar');

            throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'), 0, $previous);
        }

        self::$loaded = true;
    }

    /**
     * Bootstrap Community Builder and check the postconditions this component relies on.
     *
     * @return  string  Empty when every postcondition holds, otherwise the diagnostic
     *                  reason for the failure, to be logged rather than displayed.
     */
    private function bootstrap(): string
    {
        global $_PLUGINS;

        $requiredFiles = [
            JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php',
            JPATH_SITE . '/libraries/CBLib/CB/Application/CBApplication.php',
            JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (!is_file($requiredFile)) {
                return 'Missing Community Builder file: ' . $requiredFile;
            }
        }

        include_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';

        if (!function_exists('cbimport')) {
            return 'plugin.foundation.php did not define cbimport().';
        }

        cbimport('cb.html');
        cbimport('language.front');

        if (!isset($_PLUGINS) || !is_object($_PLUGINS)) {
            return 'Community Builder did not create the $_PLUGINS plugin handler.';
        }

        $_PLUGINS->loadPluginGroup('user');

        if (
            !$_PLUGINS->getLoadedPlugin('user', 'cbgroupjive')
            || !class_exists(CBGroupJive::class)
        ) {
            return 'The CB GroupJive plugin is not installed, not published, or failed to load.';
        }

        $_PLUGINS->loadPluginGroup('user/plug_cbgroupjive/plugins');

        if (
            !$_PLUGINS->getLoadedPlugin('user/plug_cbgroupjive/plugins', 'cbgroupjiveevents')
            || !class_exists(CBGroupJiveEvents::class)
        ) {
            return 'The CB GroupJive Events plugin is not installed, not published, or failed to load.';
        }

        return '';
    }
}
