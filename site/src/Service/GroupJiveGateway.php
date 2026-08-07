<?php

declare(strict_types=1);

namespace Joomla\Component\YSCBCalendar\Site\Service;

defined('_JEXEC') or die;

use CB\Plugin\GroupJive\CBGroupJive;
use CB\Plugin\GroupJiveEvents\CBGroupJiveEvents;
use CBLib\Registry\Registry;
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
     * Get the view access levels GroupJive uses for a user.
     *
     * @param   int  $userId  User ID
     *
     * @return  array  Access levels, or an empty array when none are available
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function getAccessLevels(int $userId): array
    {
        $this->load();

        $levels = array_values(array_unique(array_map('intval', CBGroupJive::getAccess($userId))));

        return $levels;
    }

    /**
     * Check whether GroupJive permits uncategorized groups.
     *
     * @return  bool
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function allowsUncategorizedGroups(): bool
    {
        $this->load();

        return (bool) CBGroupJive::getGlobalParams()->getInt('groups_uncategorized', 1);
    }

    /**
     * Check whether GroupJive Events permits a user to view events.
     *
     * @param   int  $userId  User ID
     *
     * @return  bool
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function canAccessEvents(int $userId): bool
    {
        $this->load();

        if (CBGroupJive::isModerator($userId)) {
            return true;
        }

        global $_PLUGINS;

        $plugin = $_PLUGINS->getLoadedPlugin('user/plug_cbgroupjive/plugins', 'cbgroupjiveevents');
        $params = new Registry();
        $params->load($plugin->params);

        return in_array($params->getInt('groups_events_access', 1), $this->getAccessLevels($userId), true);
    }

    /**
     * Get a user's Community Builder formatted name as plain text.
     *
     * @param   int  $userId  User ID
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function formatOwnerName(int $userId): string
    {
        $this->load();

        $name = \CBuser::getUserDataInstance($userId)->getFormattedName();

        return htmlspecialchars_decode($name, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * Get a user's canonical Community Builder profile URL.
     *
     * HTML escaping is disabled because the URL is returned as data; callers need
     * literal ampersands rather than `&amp;` entities.
     *
     * @param   int  $userId  User ID
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function userProfileUrl(int $userId): string
    {
        $this->load();

        if ($userId <= 0) {
            return '';
        }

        global $_CB_framework;

        return $_CB_framework->userProfileUrl($userId, false);
    }

    /**
     * Get a group's canonical GroupJive URL.
     *
     * HTML escaping is disabled because the URL is returned as data; callers need
     * literal ampersands rather than `&amp;` entities.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function groupUrl(int $groupId): string
    {
        $this->load();

        global $_CB_framework;

        return $_CB_framework->pluginClassUrl(
            'cbgroupjive',
            false,
            ['action' => 'groups', 'func' => 'show', 'id' => $groupId]
        );
    }

    /**
     * Get a group's canonical GroupJive Events tab URL.
     *
     * HTML escaping is disabled because the URL is returned as data; callers need
     * literal ampersands rather than `&amp;` entities.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the Community Builder dependencies are unavailable
     */
    public function groupEventsUrl(int $groupId): string
    {
        $this->load();

        global $_CB_framework;

        return $_CB_framework->pluginClassUrl(
            'cbgroupjive',
            false,
            ['action' => 'groups', 'func' => 'show', 'id' => $groupId, 'tab' => 'grouptabevents']
        );
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
        global $_CB_framework, $_PLUGINS;

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

        if (
            !class_exists(\CBuser::class)
            || !method_exists(\CBuser::class, 'getUserDataInstance')
            || !isset($_CB_framework)
            || !is_object($_CB_framework)
            || !method_exists($_CB_framework, 'userProfileUrl')
            || !method_exists($_CB_framework, 'pluginClassUrl')
        ) {
            return 'Community Builder did not provide its user and URL helpers.';
        }

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
