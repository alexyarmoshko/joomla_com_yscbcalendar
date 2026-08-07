<?php

namespace Joomla\CMS\MVC\Factory {
    interface MVCFactoryInterface
    {
    }
}

namespace Joomla\CMS\MVC\Model {
    use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

    class BaseDatabaseModel
    {
        public function __construct($config = [], ?MVCFactoryInterface $factory = null)
        {
        }

        public function getDatabase(): object
        {
            return new \stdClass();
        }
    }
}

namespace Joomla\CMS {
    class Factory
    {
        public static object $application;

        public static function getApplication(): object
        {
            return self::$application;
        }
    }
}

namespace {
    use Joomla\CMS\Factory;
    use Joomla\Component\YSCBCalendar\Site\Model\CalendarModel;
    use Joomla\Component\YSCBCalendar\Site\Service\GroupJiveGateway;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../site/src/Service/GroupJiveGateway.php';
    require_once __DIR__ . '/../site/src/Model/CalendarModel.php';

    final class FakeGroupJiveGateway extends GroupJiveGateway
    {
        public int $moderatorCalls = 0;

        public function isModerator(int $userId): bool
        {
            $this->moderatorCalls++;

            return $userId === 42;
        }

        public function getAccessLevels(int $userId): array
        {
            return [$userId, 7];
        }

        public function allowsUncategorizedGroups(): bool
        {
            return false;
        }

        public function canAccessEvents(int $userId): bool
        {
            return false;
        }

        public function formatOwnerName(int $userId): string
        {
            return 'Owner ' . $userId . ' & "Name" <Co>';
        }

        public function userProfileUrl(int $userId): string
        {
            return '/profile?id=' . $userId;
        }

        public function groupUrl(int $groupId): string
        {
            return '/group?id=' . $groupId;
        }

        public function groupEventsUrl(int $groupId): string
        {
            return '/events?group=' . $groupId;
        }
    }

    final class TestableCalendarModel extends CalendarModel
    {
        public function gatewayValues(): array
        {
            return [
                $this->isModerator(42),
                $this->getAccessLevels(),
                $this->allowUncategorizedGroups(),
                $this->buildGroupEventsUrl(9),
                $this->buildGroupUrl(9),
                $this->resolveOwnerName((object) ['owner_id' => 12]),
                $this->buildProfileUrl(12),
            ];
        }
    }

    function assertSameValue(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
            exit(1);
        }
    }

    Factory::$application = new class () {
        public function getIdentity(): object
        {
            return (object) ['id' => 42, 'guest' => false];
        }
    };

    $gateway = new FakeGroupJiveGateway();
    $model = new TestableCalendarModel([], null, $gateway);

    assertSameValue(
        [
            true,
            [42, 7],
            false,
            '/events?group=9',
            '/group?id=9',
            'Owner 12 & "Name" <Co>',
            '/profile?id=12',
        ],
        $model->gatewayValues(),
        'policy and enrichment calls use the injected gateway'
    );
    assertSameValue([], $model->getEvents(new \DateTimeImmutable(), new \DateTimeImmutable()), 'denied Events access returns no data');
    assertSameValue(1, $gateway->moderatorCalls, 'denied Events access skips the separate moderator lookup');

    echo 'CalendarModel gateway tests: ok' . PHP_EOL;
}
