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
        | Request URL
        |--------------------------------------------------------------------------
        */
        'request_url'      => env('UBKI_REQUEST_URL', 'https://secure.ubki.ua/b2_api_xml/ubki/xml'),

        /*
        |--------------------------------------------------------------------------
        | Upload URL
        |--------------------------------------------------------------------------
        */
        'upload_url'              => env('UBKI_UPLOAD_URL', 'https://secure.ubki.ua/upload/data/xml'),


        //-------------- TEST MODE ------------------------------------------------

        /*
        |--------------------------------------------------------------------------
        | Login of the account UBKI
        |--------------------------------------------------------------------------
        */
        'test_account_login'      => env('UBKI_TEST_ACCOUNT_LOGIN', null),

        /*
        |--------------------------------------------------------------------------
        | Password of the account UBKI
        |--------------------------------------------------------------------------
        */
        'test_account_password'   => env('UBKI_TEST_ACCOUNT_PASSWORD', null),

        /*
        |--------------------------------------------------------------------------
        | Test URL of Authorization service
        |--------------------------------------------------------------------------
        */
        'test_auth_url'           => env('UBKI_TEST_AUTH_URL', 'https://secure.ubki.ua:4040/b2_api_xml/ubki/auth'),

        /*
        |--------------------------------------------------------------------------
        | Test URL of request
        |--------------------------------------------------------------------------
        */
        'test_request_url' => env('UBKI_TEST_REQUEST_URL', 'https://secure.ubki.ua:4040/b2_api_xml/ubki/xml'),

        /*
        |--------------------------------------------------------------------------
        | Test URL of upload
        |--------------------------------------------------------------------------
        */
        'test_upload_url'         => env('UBKI_TEST_UPLOAD_URL', 'https://secure.ubki.ua:4040/upload/data/xml'),

        //-------------- END TEST MODE --------------------------------------------

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
        'report_default'          => 'standard_pb',

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

        /*
        |--------------------------------------------------------------------------
        | Mapping model data upload (attributes)
        |--------------------------------------------------------------------------
        */
        'model_data_upload'       => [
            'dlref'     => 'public_id',          // Номер заявки
            'vdate'     => 'created_at',         // Дата содания заявки
            'dldateclc' => 'upload_date',        // Дата расчета
            'dlamt'     => 'sum',                // Сумма (начальная) сделки
            'dlds'      => 'contract',           // Дата начала сделки
            'dldpf'     => 'expiration_date',    // Дата окончания сделки по договору
            'dldff'     => 'close_date',         // Фактическая дата окончания сделки
            'dlflstat'  => 'status_ubki',        // Статус сделки в тек.периоде (Код из спр.16)
            'dlamtcur'  => 'upload_debt',        // Сумма тек. задолженности
            'dlflbrk'   => 'upload_delay',       // Признак наличия просрочки в тек.периоде
            'dldayexp'  => 'upload_delay_days',  // Текущее кол-во дней просрочки
            'family'    => 'family_status',      // Семейное положение
            'sstate'    => 'type_employment',    // Социальный статус (Код из спр.6)
            'dtype'     => 'passport_type',      // Тип документа (Код из спр.7)
            'dser'      => 'passport_ser_upload',// Серия паспорта или номер записи ID-карты
            'dnom'      => 'passport_num_upload',// Номер паспорта или номер ID-карты
            'dwho'      => 'passport_issued',    // Кем выдан документ
            'dwdt'      => 'passport_date',      // Дата выдачи документа
            'dterm'     => 'passport_terminate', // Дата до которой документ действителен (для ID-карт)
            'adindex'   => 'postcode',           // Почтовый индекс
            'adstate'   => 'region',             // Область
            'adcity'    => 'city',               // Населенный пункт
            'adstreet'  => 'street',             // Улица
            'adhome'    => 'house',              // Дом
            'adflat'    => 'flat',               // Квартира
            'adactual'  => 'address_actual',     // Адреса: 1 - коме регистрации есть и адрес проживания
            'adindex2'  => 'postcode_actual',    // Почтовый индекс
            'adstate2'  => 'region_actual',      // Область
            'adcity2'   => 'city_actual',        // Населенный пункт
            'adstreet2' => 'street_actual',      // Улица
            'adhome2'   => 'house_actual',       // Дом
            'adflat2'   => 'flat_actual',        // Квартира
            'cval'      => 'phone',              // Телефон
        ],

        /*
        |--------------------------------------------------------------------------
        | Types of upload:
        |   i - Запись новой информации (повторная передача информации за тот же период по договору отсекается)
        |   u - Обновление информации (повторная передача информации за тот же период по договору перезаписывает имеющуюся)
        |   d - Удаление информации (информация за соответствующий период удаляется)
        |--------------------------------------------------------------------------
        */
        'upload_req_type'         => 'i',

        /*
        |--------------------------------------------------------------------------
        | Country
        |--------------------------------------------------------------------------
        */
        'upload_country'          => 'UA',

        /*
        |--------------------------------------------------------------------------
        | Currency (Валюта сделки (Код из спр.12))
        |    980 - Українська гривня
        |--------------------------------------------------------------------------
        */
        'upload_currency'         => '980',

        /*
        |--------------------------------------------------------------------------
        | Type of transaction (Тип сделки (Код из спр.17))
        |   7 - Кредитный договор на другие потребительские цели
        |--------------------------------------------------------------------------
        */
        'upload_transaction_type' => '7',

        /*
        |--------------------------------------------------------------------------
        | Type of collateral (Вид обеспечения (Код из спр.15))
        |   33 - Другие виды обеспечения
        |--------------------------------------------------------------------------
        */
        'upload_collateral'       => '33',

        /*
        |--------------------------------------------------------------------------
        | Repayment order (Порядок погашения (Код из спр.18))
        |   7 - Кредит с индивидуальным графиком погашения
        |--------------------------------------------------------------------------
        */
        'upload_repayment'        => '7',

        /*
        |--------------------------------------------------------------------------
        | Subject role (Роль субъекта (Код из спр.14))
        |   1 - Заемщик
        |--------------------------------------------------------------------------
        */
        'upload_subject'          => '1',

    ];