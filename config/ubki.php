<?php

    return [

        /*
        |--------------------------------------------------------------------------
        | Test mode
        |--------------------------------------------------------------------------
        */
        'test_mode'        => env('UBKI_TEST_MODE', true),

        /*
        |--------------------------------------------------------------------------
        | Login of the account UBKI
        |--------------------------------------------------------------------------
        */
        'account_login'    => env('UBKI_ACCOUNT_LOGIN', null),

        /*
        |--------------------------------------------------------------------------
        | Password of the account UBKI
        |--------------------------------------------------------------------------
        */
        'account_password' => env('UBKI_ACCOUNT_PASSWORD', null),

        /*
        |--------------------------------------------------------------------------
        | Authorization service URL
        |--------------------------------------------------------------------------
        */
        'auth_url'         => env('UBKI_AUTH_URL', 'https://secure.ubki.ua/b2_api_xml/ubki/auth'),

        /*
        |--------------------------------------------------------------------------
        | Test URL of Authorization service
        |--------------------------------------------------------------------------
        */
        'test_auth_url'    => env('UBKI_TEST_AUTH_URL', 'https://secure.ubki.ua:4040/b2_api_xml/ubki/auth'),

        /*
        |--------------------------------------------------------------------------
        | Request URL
        |--------------------------------------------------------------------------
        */
        'request_url'      => env('UBKI_REQUEST_URL', 'https://secure.ubki.ua/b2_api_xml/ubki/xml'),

        /*
        |--------------------------------------------------------------------------
        | Test URL of request
        |--------------------------------------------------------------------------
        */
        'test_request_url' => env('UBKI_TEST_REQUEST_URL', 'https://secure.ubki.ua:4040/b2_api_xml/ubki/xml'),

        /*
        |--------------------------------------------------------------------------
        | Types of report
        |--------------------------------------------------------------------------
        */
        'reports'          => [
            'standard'    => '09', // Стандартный кредитный отчет (без ПриватБанка)
            'standard_pb' => '10', // Стандартный кредитный отчет (с ПриватБанком)
            'contacts'    => '04', // Контакты
            'scoring'     => '11', // Кредитный балл
            'ident'       => '12', // Идентификация
            'passport'    => '13', // Сервис проверки паспорта в МВД
        ],

        /*
        |--------------------------------------------------------------------------
        | Default type of report
        |--------------------------------------------------------------------------
        */
        'report_default'   => 'standard',

        /*
        |--------------------------------------------------------------------------
        | Languages of search
        |--------------------------------------------------------------------------
        */
        'languages'        => [
            'ua' => 1, // Украинский
            'ru' => 2, // Русский
            'ge' => 3, // Грузинский
            'en' => 4, // Английский
            'lv' => 5, // Латышский
            'gr' => 6, // Греческий
            'cn' => 7, // Китайский
            'kz' => 8, // Казахский

        ],

        /*
        |--------------------------------------------------------------------------
        | Default language of search
        |--------------------------------------------------------------------------
        */
        'lang_default'     => 'ua',

        /*
        |--------------------------------------------------------------------------
        | Mapping model data (attributes)
        |--------------------------------------------------------------------------
        */
        'model_data'       => [
            'okpo'  => 'inn',           // ИНН
            'lname' => 'lastName',      // Фамилия
            'fname' => 'firstName',     // Имя
            'mname' => 'middleName',    // Отчество
            'bdate' => 'birth_date',    // Дата рождения (гггг-мм-дд)
            'dtype' => 'passport_type', // Тип паспорта (см. справочник "Тип документа")
            'dser'  => 'passport_ser',  // Серия паспорта или номер записи ID-карты
            'dnom'  => 'passport_num',  // Номер паспорта или номер ID-карты
            'ctype' => 'contact_type',  // Тип контакта (см. справочник "Тип контакта")
            'cval'  => 'contact_val',   // Значение контакта (например: "+380951111111")
        ],
    ];