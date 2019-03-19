<?php

namespace OptimistDigital\NovaPageManager;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class NovaPageManager extends Tool
{
    private static $templates = [];

    public function __construct(array $templates = [])
    {
        self::$templates = $templates;
    }

    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('nova-page-manager', __DIR__ . '/../dist/js/tool.js');
        Nova::style('nova-page-manager', __DIR__ . '/../dist/css/tool.css');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('nova-page-manager::navigation');
    }

    public static function getTemplates(): array
    {
        return self::$templates;
    }
}