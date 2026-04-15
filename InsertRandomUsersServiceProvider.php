<?php

namespace Totocsa\InsertRandomUsers;

use Illuminate\Support\ServiceProvider;

class InsertRandomUsersServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Ha van konfigurációs fájl, azt itt töltheted be
        //$this->mergeConfigFrom(__DIR__.'/../config/destroy-confirm-modal.php', 'destroy-confirm-modal');
    }

    public function boot()
    {
        // Publikálható migrációk
        $this->publishes([
            __DIR__ . '/database/seeders/' => database_path('seeders'),
        ], 'laravel-insert-random-users-seeders');
    }
}
