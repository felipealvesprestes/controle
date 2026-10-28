<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Administrator
    |--------------------------------------------------------------------------
    |
    | Used by Database\Seeders\AdminUserSeeder to create the first
    | administrator (RN-17). Keep these values in .env, never in the
    | repository. Without a password, the seeder sets a random one and
    | the administrator defines their own through "forgot password".
    |
    */

    'name' => env('ADMIN_NAME', 'Administrator'),

    'email' => env('ADMIN_EMAIL', ''),

    'password' => env('ADMIN_PASSWORD', ''),

];
