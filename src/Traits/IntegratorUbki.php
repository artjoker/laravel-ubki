<?php

    namespace Artjoker\LaravelUbki\Traits;

    use Artjoker\LaravelUbki\Facades\LaravelUbki;

    trait IntegratorUbki
    {

        /**
         * get Report UBKI
         *
         * @param $params
         *
         * @return mixed
         */
        public function ubki($params = [])
        {
            if (method_exists($this, 'ubkiAttributes')) {
                $this->ubkiAttributes();
            }
            return LaravelUbki::getReport($this->getAttributes(), $params);
        }

    }
