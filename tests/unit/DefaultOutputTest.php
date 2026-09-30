<?php

namespace Winter\SEO\Tests\Unit;

use Backend\Models\BrandSetting;
use System\Tests\Bootstrap\TestCase;
use Url;
use Winter\SEO\Classes\Link;
use Winter\SEO\Classes\Meta;
use Winter\SEO\Components\SEOTags;

/**
 * The gate for backward compatibility on this plugin.
 *
 * A feature that is meant to be additive must not change what the plugin emits on
 * a default install with nothing configured. That is invisible in review and it is
 * exactly what breaks a site silently, so it is pinned here: the **set of keys**
 * emitted is asserted exactly, and each value is asserted against its own source
 * rather than a literal.
 *
 * Asserting against the source instead of a literal is deliberate. A literal would
 * couple the test to the local database's brand name and to whatever host the CLI
 * kernel resolves, so renaming the site would fail the test for no reason and the
 * gate would get deleted. Asserting the derivation still catches the change that
 * matters — code that stopped calling BrandSetting, or that stopped deriving
 * og:url from the canonical URL — while the exact key list catches the addition or
 * removal of any tag.
 */
class DefaultOutputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Meta and Link are protected static state that persists between tests in
        // the process. Refresh both, or this test is order-dependent.
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
     * Build the component with no constructor and a page carrying only empty SEO
     * properties, which is the shape of a default install
     */
    protected function renderDefaultTags(): array
    {
        $component = (new \ReflectionClass(SEOTags::class))->newInstanceWithoutConstructor();

        $page = new \stdClass;
        foreach (['meta_title', 'meta_description', 'meta_image', 'meta_nofollow', 'paginatePrev', 'paginateNext'] as $property) {
            $page->{$property} = '';
        }

        $this->setProtectedProperty($component, 'page', $page);

        return [$component->getMetaTags(), $component->getLinkTags()];
    }

    public function testDefaultMetaTags()
    {
        [$meta] = $this->renderDefaultTags();

        $this->assertSame(['og:url', 'og:type', 'og:site_name'], array_keys($meta));

        $this->assertSame('website', $meta['og:type']);
        $this->assertSame(Url::current(), $meta['og:url']);
        $this->assertSame(BrandSetting::get('app_name'), $meta['og:site_name']);
    }

    public function testDefaultLinkTags()
    {
        [, $link] = $this->renderDefaultTags();

        $this->assertSame(['canonical'], array_keys($link));

        $this->assertSame(Url::current(), $link['canonical']);
    }

    /**
     * A page that configures nothing must produce the same output every time. This
     * is here because the two tests above pass on a single run by construction, and
     * static state carrying over between tests is the failure mode that makes a gate
     * flaky and gets it deleted.
     */
    public function testDefaultOutputIsRepeatable()
    {
        [$firstMeta, $firstLink] = $this->renderDefaultTags();
        [$secondMeta, $secondLink] = $this->renderDefaultTags();

        $this->assertSame($firstMeta, $secondMeta);
        $this->assertSame($firstLink, $secondLink);
    }
}