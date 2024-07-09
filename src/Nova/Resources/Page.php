<?php

namespace Outl1ne\PageManager\Nova\Resources;

use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Kongulov\NovaTabTranslatable\TranslatableTabToRowTrait;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Panel;
use Illuminate\Http\Request;
use Mostafaznv\NovaCkEditor\CkEditor;
use Outl1ne\PageManager\NPM;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Outl1ne\PageManager\Template;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\PageManager\Nova\Fields\PageLinkField;
use Outl1ne\PageManager\Nova\Fields\PrefixSlugField;
use Outl1ne\PageManager\Nova\Fields\PageManagerField;
use Outl1ne\PageManager\Nova\Filters\TemplatesUniqueFilter;
use Outl1ne\PageManager\Nova\Filters\TemplatesExcludeFilter;
use Outl1ne\PageManager\Nova\Filters\TemplatesIncludeFilter;

class Page extends TemplateResource
{

    use TranslatableTabToRowTrait;

    public static $title = 'name';
    public static $model = null;
    public static $displayInNavigation = false;
    public static $search = ['name', 'slug', 'content', 'template'];

    protected $type = 'page';



    // ------------------------------
    // Core resource setup
    // ------------------------------

    public function __construct($resource)
    {
        self::$model = NPM::getPageModel();
        parent::__construct($resource);
    }

    public static function newModel()
    {
        $model = empty(self::$model) ? NPM::getPageModel() : self::$model;
        return new $model;
    }

    public function title()
    {
        return "{$this->name} ({$this->slug})";
    }

    public static function label()
    {
        return __('novaPageManager.pageResourceLabel');
    }

    public static function singularLabel()
    {
        return __('novaPageManager.pageResourceSingularLabel');
    }



    // ------------------------------
    // Fields
    // ------------------------------

    public function fields(Request $request)
    {
        [$pathPrefix, $pathSuffix] = $this->getPathPrefixAndSuffix();

        return [
            // Name field
            Text::make(__('novaPageManager.nameField'), 'name')
                ->translatable(NPM::getLocales())
                ->rules('required', 'max:255')
                ->showOnPreview(),

            // Slug on form views
            PrefixSlugField::make(__('novaPageManager.slugField'), 'slug')
                ->translatable(NPM::getLocales())
                ->from('name.*')
                ->onlyOnForms()
                ->pathPrefix($pathPrefix)
                ->pathSuffix($pathSuffix)
                ->rules('required'),

            // Slug on index and detail views
            PageLinkField::make(__('novaPageManager.slugField'), 'path')
                ->exceptOnForms()
                ->withPageUrl(NPM::getBaseUrl($this->resource))
                ->translatable(NPM::getLocales())
                ->showOnPreview(),

            // Template selector
            Select::make(__('novaPageManager.templateField'), 'template')
                ->options(fn () => $this->getTemplateOptions(Template::TYPE_PAGE))
                ->rules('required', 'max:255')
                ->displayUsingLabels()
                ->showOnPreview(),

            // Parent selector
            Select::make('Parent page', 'parent_id')
                ->options($this->getParentOptions())
                ->hideFromIndex()
                ->hideFromDetail()
                ->displayUsingLabels()
                ->nullable()
                ->showOnPreview(),

            NovaTabTranslatable::make([
                CkEditor::make('Content','content')
                    ->translatable(NPM::getLocales())->hideFromIndex(),
            ]),
            // Page data panel
            Panel::make(__('novaPageManager.sidebarTitle'), [


                PageManagerField::make(\Outl1ne\PageManager\Template::TYPE_PAGE)
                    ->withTemplate($this->template)
                    ->withSeoFields(NPM::getSeoFields())
                    ->hideWhenCreating(),
            ])
        ];
    }



    // --------------------
    // Page Manager Helpers
    // --------------------

    public function getParentOptions()
    {
        $page = NPM::getPageModel();
        if ($this->resource?->id) {
            $pages = $page::query()
                ->where('id', '<>', $this->id)
                ->where(fn ($query) => $query
                    ->whereNull('parent_id')
                    ->orWhere('parent_id', '<>', $this->id))
                ->get();
        } else {
            $pages = $page::all();
        }
        return $pages->pluck('name', 'id');
    }

    protected function getPathPrefixAndSuffix()
    {
        $pathPrefix = []; // translatable
        $pathSuffix = null;

        if ($this->resource?->id) {
            $path = $this->path ?? [];
            $locales = NPM::getLocales();
            $pathSuffix = $this->template?->pathSuffix();

            foreach ($locales as $key => $localeName) {
                // Explode path and remove page's own path + suffix if it has one
                $explodedPath = explode('/', $path[$key]);
                if (!empty($pathSuffix)) array_pop($explodedPath); // Remove suffix
                array_pop($explodedPath); // Remove own path
                $localePrefix = implode('/', $explodedPath);
                $pathPrefix[$key] = !empty($localePrefix) ? "{$localePrefix}/" : null;
            }
        }

        return [$pathPrefix, $pathSuffix];
    }

    public function filters(NovaRequest $request)
    {
        return [
            TemplatesUniqueFilter::make('pages'),
            TemplatesIncludeFilter::make('pages'),
            TemplatesExcludeFilter::make('pages'),
        ];
    }
}
