<?php

    namespace Artjoker\LaravelUbki\Facades;

    use Illuminate\Support\Facades\Facade;

    class LaravelUbki extends Facade
    {
        /**
         * Get the registered name of the component.
         *
         * @return string
         */
        protected static function getFacadeAccessor()
        {
            return 'laravelubki';
        }
    }
