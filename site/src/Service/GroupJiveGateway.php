<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Service;

defined('_JEXEC') or die;

use CB\Plugin\GroupJive\CBGroupJive;
use CB\Plugin\GroupJiveEvents\CBGroupJiveEvents;
use Joomla\CMS\Language\Text;

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

        global $_PLUGINS;

        $requiredFiles = [
            JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php',
            JPATH_SITE . '/libraries/CBLib/CB/Application/CBApplication.php',
            JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (!is_file($requiredFile)) {
                throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'));
            }
        }

        try {
            include_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';

            if (!function_exists('cbimport')) {
                throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'));
            }

            cbimport('cb.html');
            cbimport('language.front');

            if (!isset($_PLUGINS) || !is_object($_PLUGINS)) {
                throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'));
            }

            $_PLUGINS->loadPluginGroup('user');

            if (
                !$_PLUGINS->getLoadedPlugin('user', 'cbgroupjive')
                || !class_exists(CBGroupJive::class)
            ) {
                throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'));
            }

            $_PLUGINS->loadPluginGroup('user/plug_cbgroupjive/plugins');

            if (
                !$_PLUGINS->getLoadedPlugin('user/plug_cbgroupjive/plugins', 'cbgroupjiveevents')
                || !class_exists(CBGroupJiveEvents::class)
            ) {
                throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'));
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException(Text::_('COM_YSCBCALENDAR_ERROR_DEPENDENCY'), 0, $e);
        }

        self::$loaded = true;
    }
}
