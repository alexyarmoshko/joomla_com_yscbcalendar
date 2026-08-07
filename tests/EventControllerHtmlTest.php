<?php

namespace Joomla\CMS\MVC\Controller {
    class BaseController
    {
    }
}

namespace Joomla\CMS\Uri {
    class Uri
    {
        public static function root(bool $pathonly = false): string
        {
            return $pathonly ? '/subsite' : 'https://example.test/subsite/';
        }
    }
}

namespace Joomla\Filter {
    class InputFilter
    {
        public const ONLY_ALLOW_DEFINED_TAGS = 0;
        public const ONLY_ALLOW_DEFINED_ATTRIBUTES = 0;

        public function __construct(...$arguments)
        {
        }

        public function clean(string $html, string $type): string
        {
            return $html;
        }
    }
}

namespace {
    use Joomla\Component\YSCBCalendar\Site\Controller\EventController;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../site/src/Controller/EventController.php';

    final class TestableEventController extends EventController
    {
        public function sanitize(string $html): string
        {
            return $this->sanitizeHtml($html);
        }
    }

    function assertSameHtml(string $expected, string $actual, string $message): void
    {
        if ($expected !== $actual) {
            fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
            exit(1);
        }
    }

    $controller = new TestableEventController();
    // Relative links are deliberately outside this observed image-failure fix.
    $html = '<p><img src="images/event.jpeg"><img src="/images/root.jpeg">'
        . '<img src="https://cdn.example/image.jpeg"><a href="images/file.pdf">File</a></p>';

    assertSameHtml(
        '<p><img src="/subsite/images/event.jpeg"><img src="/images/root.jpeg">'
            . '<img src="https://cdn.example/image.jpeg"><a href="images/file.pdf">File</a></p>',
        $controller->sanitize($html),
        'relative event images use the Joomla site-root path without changing other URLs'
    );

    // The sanitizer re-emits tag names as written, so `<IMG>` reaches this path; the host
    // is case-sensitive, so the directory must survive the rewrite unchanged.
    assertSameHtml(
        '<IMG src="/subsite/Images/event.jpeg">',
        $controller->sanitize('<IMG src="Images/event.jpeg">'),
        'an uppercase tag is rewritten without folding the directory case'
    );

    echo 'EventController image URL tests: ok' . PHP_EOL;
}
