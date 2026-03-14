<?php

namespace Botble\RezgoConnector;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Illuminate\Support\Facades\Schema;

class Plugin extends PluginOperationAbstract
{
    public static function activated(): void
    {
        // Run plugin-specific migrations when plugin is activated
        app('migrator')->run(__DIR__ . '/../database/migrations');
    }

    public static function remove(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('rezgo_meta');
        Schema::enableForeignKeyConstraints();
    }
}
