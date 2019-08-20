<?php

    namespace Artjoker\LaravelUbki;

    use Illuminate\Support\ServiceProvider;

    class LaravelUbkiServiceProvider extends ServiceProvider
    {
        /**
         * Perform post-registration booting of services.
         *
         * @return void
         */
        public function boot()
        {
            // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'artjoker');
            // $this->loadViewsFrom(__DIR__.'/../resources/views', 'artjoker');
            // $this->loadRoutesFrom(__DIR__.'/routes.php');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__ . '/../config/ubki.php' => config_path('ubki.php'),
            ], 'laravelubki.config');

            // Publishing is only necessary when using the CLI.
            if ($this->app->runningInConsole()) {
                $this->bootForConsole();
            }
        }

        /**
         * Register any package services.
         *
         * @return void
         */
        public function register()
        {
            $this->mergeConfigFrom(__DIR__ . '/../config/ubki.php', 'ubki');

            // Register the service the package provides.
            $this->app->singleton('laravelubki', function ($app) {
                return new LaravelUbki;
            });
        }

        /**
         * Get the services provided by the provider.
         *
         * @return array
         */
        public function provides()
        {
            return ['laravelubki'];
        }

        /**
         * Console-specific booting.
         *
         * @return void
         */
        protected function bootForConsole()
        {
            // Publishing the configuration file.
            $this->publishes([
                __DIR__ . '/../config/ubki.php' => config_path('ubki.php'),
            ], 'laravelubki.config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/artjoker'),
            ], 'laravelubki.views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/artjoker'),
            ], 'laravelubki.views');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/artjoker'),
            ], 'laravelubki.views');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }
