<?php

namespace Winter\SEO\Tests\Unit;

use ReflectionClass;
use ReflectionMethod;
use System\Tests\Bootstrap\TestCase;
use Winter\SEO\Classes\Meta;
use Winter\SEO\Components\SEOTags;

class SEOTagsComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Meta::refresh();
    }

    protected function tearDown(): void
    {
        Meta::refresh();

        parent::tearDown();
    }

    /**
     * Invoke the protected processOgImage method on an instance created without
     * running the constructor, which would require a CMS page and controller
     */
    protected function processOgImage(): void
    {
        $component = (new ReflectionClass(SEOTags::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod(SEOTags::class, 'processOgImage'))->invoke($component);
    }

    /**
     * The image type is resolved from the image URL, including when the width
     * and height are supplied through the SeoableModel mappings and the image
     * is therefore not resized
     */
    public function testOgImageTypeIsResolvedWhenDimensionsAreProvided()
    {
        Meta::set('og:image', 'https://example.com/uploads/image.jpg');
        Meta::set('og:image:width', '1200');
        Meta::set('og:image:height', '630');
        Meta::set('og:image:alt', 'An image');

        $this->processOgImage();

        $this->assertSame('image/jpeg', Meta::get('og:image:type'));
    }

    /**
     * The image is resized when the width or the height is missing, and the
     * image type is resolved from the resized URL
     */
    public function testOgImageTypeIsResolvedWhenTheImageIsResized()
    {
        Meta::set('og:image', 'https://example.com/uploads/image.jpg');
        Meta::set('og:image:alt', 'An image');

        $this->processOgImage();

        $this->assertSame('image/jpeg', Meta::get('og:image:type'));
    }
}
