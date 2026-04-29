<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Primary cloud disk: Contabo Object Storage (S3-compatible).
        // Contabo requires explicit endpoint + path-style addressing.
        's3' => [
            'driver' => 's3',
            'key' => env('CONTABO_ACCESS_KEY_ID'),
            'secret' => env('CONTABO_SECRET_ACCESS_KEY'),
            'region' => env('CONTABO_DEFAULT_REGION', 'eu-central-1'),
            'bucket' => env('CONTABO_BUCKET'),
            'endpoint' => env('CONTABO_ENDPOINT'),
            'use_path_style_endpoint' => env('CONTABO_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

        // Alias for clarity in code that wants to be explicit about which
        // object store it's writing to. Same credentials as `s3`.
        'contabo' => [
            'driver' => 's3',
            'key' => env('CONTABO_ACCESS_KEY_ID'),
            'secret' => env('CONTABO_SECRET_ACCESS_KEY'),
            'region' => env('CONTABO_DEFAULT_REGION', 'eu-central-1'),
            'bucket' => env('CONTABO_BUCKET'),
            'endpoint' => env('CONTABO_ENDPOINT'),
            'use_path_style_endpoint' => env('CONTABO_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
