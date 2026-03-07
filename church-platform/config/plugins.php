<?php

/**
 * Core plugins — always booted, cannot be disabled via admin panel.
 * These are listed here so PluginManager always boots them first.
 *
 * Feature plugins are controlled by storage/app/plugins.json.
 */

return [

    'core' => [
        'Auth',
        'Settings',
    ],

    /**
     * Plugin discovery path.
     */
    'path' => base_path('plugins'),

    /**
     * Registry file path (enable/disable state).
     */
    'registry' => storage_path('app/plugins.json'),

];
