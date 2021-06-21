<?php

namespace Distilleries\Contentful;

class ContentfulUtilities
{
    public static $runsMigrations = true;

    /**
     * Configure Contentful utilities to not register its migrations.
     *
     * @return void
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;
    }
}