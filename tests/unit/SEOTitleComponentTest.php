<?php

namespace Winter\SEO\Tests\Unit;

use System\Tests\Bootstrap\TestCase;
use Winter\SEO\Classes\Link;
use Winter\SEO\Classes\Meta;
use Winter\SEO\Components\SEOTitle;

/**
 * The SEOTitle component is opt-in by placement: it is only exercised here,
 * and the default-output gate is what proves adding it changed nothing for a
 * theme that never places it.
 *
 * The affixes are component properties rather than config keys on purpose. A
 * config read of winter.seo::... cannot be tested under this harness, so a
 * config-driven component would ship untested; a theme passing
 * `{% component 'seotitle' prefix="Site: " %}` is both testable and the more
 * idiomatic way to configure a CMS component.
 */
class SEOTitleComponentTest extends TestCase
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

    protected function runComponent($page = null, array $properties = []): SEOTitle
    {
        $component = (new \ReflectionClass(SEOTitle::class))->newInstanceWithoutConstructor();

        $this->setProtectedProperty($component, 'page', $page);

        foreach ($properties as $name => $value) {
            $component->{$name} = $value;
        }

        $component->onRun();

        return $component;
    }

    public function testTitleComesFromOgTitleWhenPresent()
    {
        Meta::set('og:title', 'A page title');

        $this->runComponent();

        $this->assertSame('A page title', Meta::get('title'));
    }

    public function testTitleFallsBackToThePageTitle()
    {
        $component = $this->runComponent(['title' => 'Fallback title']);

        $this->assertSame('Fallback title', $component->title);
        $this->assertSame('Fallback title', Meta::get('title'));
    }

    public function testNothingIsSetWhenThereIsNoTitleAnywhere()
    {
        $component = $this->runComponent(['title' => '']);

        $this->assertNull(Meta::get('title'));
        $this->assertEmpty($component->title);
    }

    public function testAffixesAreTakenFromTheComponentProperties()
    {
        $component = $this->runComponent(
            ['title' => 'A page'],
            ['prefix' => 'Site: ', 'suffix' => ' (demo)', 'separator' => ' — ']
        );

        $this->assertSame('Site: ', $component->prefix);
        $this->assertSame(' (demo)', $component->suffix);
        $this->assertSame(' — ', $component->separator);
    }

    public function testAffixesDefaultToNoDecoration()
    {
        $component = $this->runComponent(['title' => 'A page']);

        $this->assertSame('', $component->prefix);
        $this->assertSame('', $component->suffix);
        $this->assertSame(' | ', $component->separator);
    }
}