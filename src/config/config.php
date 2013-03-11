<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Basset is built around collections. A collection contains assets for
    | your application. Collections can contain both stylesheets and
    | javascripts.
    |
    | A default "application" collection is ready for immediate use. It makes
    | a couple of assumptions about your directory structure.
    |
    | /app
    |    /assets
    |        /stylesheets
    |            /less
    |            /sass
    |        /javascripts
    |            /coffeescripts
    |
    | You can overwrite this collection or remove it by publishing the config.
    |
    */

    'collections' => array(

        'application' => function($collection)
        {
            // Switch to the stylesheets directory and require the "less" and "sass" directories.
            // These directories both have a filter applied to them so that the built
            // collection will contain valid CSS.
            $directory = $collection->directory('../app/assets/stylesheets', function($collection)
            {
                $collection->requireDirectory('less')->apply('LessFilter')->to('*.less')->findMissingConstructorArgs();
                $collection->requireDirectory('sass')->apply('Sass\ScssFilter')->to('*.(sass|scss)')->findMissingConstructorArgs();
                $collection->requireDirectory();
            });

            $directory->apply('UriRewriteFilter');

            // Switch to the javascripts directory and require the "coffeescript" directory. As
            // with the above directories we'll apply the CoffeeScript filter to the directory
            // so the built collection contains valid JS.
            $collection->directory('../app/assets/javascripts', function($collection)
            {
                $collection->requireDirectory('coffeescripts')->apply('CoffeeScriptFilter')->to('*.coffee')->findMissingConstructorArgs();
                $collection->requireDirectory();
            });
        }

    ),

    /*
    |--------------------------------------------------------------------------
    | Production Environment
    |--------------------------------------------------------------------------
    |
    | Basset needs to know what your production environment is so that it can
    | respond with the correct assets. When in production Basset will attempt
    | to return any built collections. If a collection has not been built
    | Basset will dynamically route to each asset in the collection and apply
    | the filters.
    |
    | The last method can be very taxing so it's highly recommended that
    | collections are built when deploying to a production environment.
    |
    | You can supply an array of production environment names if you need to.
    |
    */

    'production' => array('production', 'prod'),

    /*
    |--------------------------------------------------------------------------
    | Build Path
    |--------------------------------------------------------------------------
    |
    | When assets are built with Artisan they will be stored within a directory
    | relative to the public directory.
    |
    | If the directory does not exist Basset will attempt to create it.
    |
    */

    'build_path' => 'assets',

    /*
    |--------------------------------------------------------------------------
    | Node Paths
    |--------------------------------------------------------------------------
    |
    | Many filters use Node to build assets. We recommend you install your
    | Node modules locally under app/assets, however you can specify additional
    | paths to your modules.
    |
    */

    'node_paths' => array(

        app_path().'/assets/node_modules'

    ),

    /*
    |--------------------------------------------------------------------------
    | Asset and Filter Aliases
    |--------------------------------------------------------------------------
    |
    | You can define aliases for commonly used assets or filters.
    | An example of an asset alias:
    |
    |   'layout' => 'stylesheets/layout/master.css'
    |
    | Filter aliases are slightly different. You can define a simple alias
    | similar to an asset alias.
    |
    |   'YuiCss' => 'Yui\CssCompressorFilter'
    |
    | However if you want to pass in options to an aliased filter then define
    | the alias as a nested array. The key should be the filter and the value
    | should be a callback closure where you can set parameters for a filters
    | constructor, etc.
    |
    |   'YuiCss' => array(
    |       'Yui\CssCompressorFilter' => function($filter)
    |       {
    |           $filter->setArguments('path/to/jar');
    |       }
    |   )
    |
    |
    */

    'aliases' => array(

        'assets' => array(),

        'filters' => array()

    )

);