<?php

namespace Winter\SEO\Components;

use Backend\Models\BrandSetting;
use Cms\Classes\ComponentBase;
use Winter\SEO\Classes\Meta;

/**
 * Renders a <title> element for the current page.
 *
 * This component is opt-in by placement: it outputs nothing at all until a
 * theme puts `{% component 'seotitle' %}` in its layout. It deliberately does
 * not fold into the seotags component, because a <title> is not a meta tag and
 * because a plugin that emits one by default would double up with the themes
 * that already write their own.
 *
 * Placing it also sets `Meta::set('title', ...)`, which is what the README's
 * example reads via `Meta::get('title')` — a value nothing in the plugin
 * otherwise writes.
 */
class SEOTitle extends ComponentBase
{
    /**
     * @var string The title for the current page
     */
    public $title;

    /**
     * @var string The site name, appended when it is not already the whole title
     */
    public $appName;

    /**
     * @var string Placed before the title
     */
    public $prefix = '';

    /**
     * @var string Placed after the title
     */
    public $suffix = '';

    /**
     * @var string Joins the title to the site name
     */
    public $separator = ' | ';

    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Page Title Component',
            'description' => 'Renders a <title> element for the current page. Place it once in your layout where the title belongs. Accepts prefix, suffix and separator.',
        ];
    }

    /**
     * Builds the title parts
     */
    public function onRun()
    {
        $title = Meta::get('og:title') ?? Meta::get('title') ?? '';

        if (empty($title) && $this->page) {
            $title = $this->page['title'] ?? $this->page['meta_title'] ?? '';
        }

        $this->title = $title;
        $this->appName = BrandSetting::get('app_name');

        // Keep the documented Meta::get('title') read in the README working for
        // themes that place this component and then follow that example.
        if (!empty($title)) {
            Meta::set('title', $title);
        }
    }
}