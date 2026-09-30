<?php

namespace Winter\SEO\Tests\Unit;

use ReflectionClass;
use ReflectionMethod;
use System\Tests\Bootstrap\TestCase;
use Winter\SEO\Classes\Link;
use Winter\SEO\Classes\Meta;
use Winter\SEO\Components\SEOTags;

class SEOTagsRobotsTest extends TestCase
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

    /**
     * Invoke processPageMeta on an instance created without the constructor, with a
     * page that carries only a nofollow setting
     */
    protected function processPageMetaWithNofollow(): void
    {
        $component = (new ReflectionClass(SEOTags::class))->newInstanceWithoutConstructor();

        $page = new \stdClass;
        foreach (['meta_title', 'meta_description', 'meta_image', 'paginatePrev', 'paginateNext'] as $property) {
            $page->{$property} = '';
        }
        $page->meta_nofollow = 1;

        $this->setProtectedProperty($component, 'page', $page);

        (new ReflectionMethod(SEOTags::class, 'processPageMeta'))->invoke($component);
    }

    /**
     * A robots value configured site-wide is data the administrator entered. Setting
     * nofollow on one page must not delete it.
     */
    public function testNofollowDoesNotDiscardAConfiguredRobotsValue()
    {
        Link::set('robots', 'index, follow');

        $this->processPageMetaWithNofollow();

        $this->assertSame('index, follow, nofollow', Link::get('robots'));
    }

    /**
     * With nothing configured, the output is unchanged: this is the default-install
     * case and it must be identical before and after the fix.
     */
    public function testNofollowIsUnchangedWhenNothingIsConfigured()
    {
        $this->processPageMetaWithNofollow();

        $this->assertSame('nofollow', Link::get('robots'));
    }

    /**
     * A page that does not set nofollow leaves a configured robots value alone
     */
    public function testConfiguredRobotsIsUntouchedWithoutNofollow()
    {
        Link::set('robots', 'index, follow');

        $component = (new ReflectionClass(SEOTags::class))->newInstanceWithoutConstructor();
        $page = new \stdClass;
        foreach (['meta_title', 'meta_description', 'meta_image', 'meta_nofollow', 'paginatePrev', 'paginateNext'] as $property) {
            $page->{$property} = '';
        }
        $this->setProtectedProperty($component, 'page', $page);

        (new ReflectionMethod(SEOTags::class, 'processPageMeta'))->invoke($component);

        $this->assertSame('index, follow', Link::get('robots'));
    }
}