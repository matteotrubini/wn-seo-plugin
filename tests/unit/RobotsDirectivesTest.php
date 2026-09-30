<?php

namespace Winter\SEO\Tests\Unit;

use Config;
use System\Tests\Bootstrap\TestCase;
use Winter\SEO\Classes\Link;
use Winter\SEO\Classes\Meta;
use Winter\SEO\Components\SEOTags;

/**
 * Covers the optional robots directives: per-page index and follow, and the
 * site-wide default. Every case here composes with whatever was already set,
 * because replacing it is the defect PR #19 fixes upstream.
 */
class RobotsDirectivesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Meta::refresh();
        Link::refresh();
    }

    protected function tearDown(): void
    {
        Meta::refresh();
        Link::refresh();

        parent::tearDown();
    }

    protected function renderRobotsForPage(array $properties): void
    {
        $component = (new \ReflectionClass(SEOTags::class))->newInstanceWithoutConstructor();

        $page = new \stdClass;
        foreach (['meta_title', 'meta_description', 'meta_image', 'meta_nofollow', 'paginatePrev', 'paginateNext', 'meta_index', 'meta_follow'] as $property) {
            $page->{$property} = '';
        }

        foreach ($properties as $property => $value) {
            $page->{$property} = $value;
        }

        $this->setProtectedProperty($component, 'page', $page);

        $component->getMetaTags();
    }

    public function testNoindexOnThePageIsEmitted()
    {
        $this->renderRobotsForPage(['meta_index' => 'noindex']);

        $this->assertSame('noindex', Link::get('robots'));
    }

    public function testNofollowOnThePageIsEmitted()
    {
        $this->renderRobotsForPage(['meta_follow' => 'nofollow']);

        $this->assertSame('nofollow', Link::get('robots'));
    }

    public function testPageDirectivesComposeWithAnExistingValue()
    {
        Link::set('robots', 'index, follow');

        $this->renderRobotsForPage(['meta_index' => 'noindex']);

        $this->assertSame('index, follow, noindex', Link::get('robots'));
    }

    public function testExistingNofollowSwitchStillComposes()
    {
        Link::set('robots', 'index, follow');

        $this->renderRobotsForPage(['meta_nofollow' => 1]);

        $this->assertSame('index, follow, nofollow', Link::get('robots'));
    }

    public function testDuplicateDirectivesAreEmittedOnce()
    {
        Link::set('robots', 'nofollow');

        $this->renderRobotsForPage(['meta_nofollow' => 1, 'meta_follow' => 'nofollow']);

        $this->assertSame('nofollow', Link::get('robots'));
    }

    public function testNothingConfiguredEmitsNoRobotsTag()
    {
        $this->renderRobotsForPage([]);

        $this->assertNull(Link::get('robots'));
    }
}