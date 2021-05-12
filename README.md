# Laravel-Ubki

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

[Украинское бюро кредитных историй (УБКИ)][link-ubki] занимается сбором, хранением, обработкой и предоставлением кредитных историй. УБКИ получает информацию о заемщиках от банков, страховых компаний, лизинговых компаний, кредитных союзов и других финансовых институтов. Информация передается на добровольной основе и только при наличии письменного согласия заемщика.

Для автоматизации взаимодействия с УБКИ существует [web-сервис][link-ubki-api], который принимает запросы, обрабатывает и выдает ответ в зависимости от типа запроса. 

This package allows you to simply and easily work with the web-service UBKI.

## Migration to version 3
In this version we added possibility to use two UBKI accounts.

To use this possibility you should update your config file and add new migration.

Before publishing config you should remove previous config file.
``` bash
$ php artisan vendor:publish --tag="laravelubki.config"
$ php artisan vendor:publish --tag="laravelubki.migrations"
```

Next, you need to run migrations:
```bash
$ php artisan migrate
```

add environment variables (`.env`)
```
UBKI_SECOND_ACCOUNT_LOGIN=
UBKI_SECOND_ACCOUNT_PASSWORD=
```

For switching between accounts you should add to params:
 - to select second account 
```php
$params = [
    'test' => false,
    'use_second_account_login' => true
];
```

 - to select main account 
```php
$params = [
    'test' => false,
    'use_second_account_login' => false
];
```

if you not select what account to use, last used account will be executed. 

## Installation

You can install this package via [Composer](http://getcomposer.org/): 

``` bash
$ composer require artjoker/laravel-ubki
```
Next, you need to run migrations:
```bash
$ php artisan migrate
```

Set environment variable (`.env`)
```
UBKI_TEST_MODE=true
UBKI_ACCOUNT_LOGIN=
UBKI_ACCOUNT_PASSWORD=
UBKI_AUTH_URL=https://secure.ubki.ua/b2_api_xml/ubki/auth
UBKI_REQUEST_URL=https://secure.ubki.ua/b2_api_xml/ubki/xml
UBKI_UPLOAD_URL=https://secure.ubki.ua/upload/data/xml
UBKI_TEST_AUTH_URL=https://secure.ubki.ua:4040/b2_api_xml/ubki/auth
UBKI_TEST_REQUEST_URL=https://secure.ubki.ua:4040/b2_api_xml/ubki/xml
UBKI_TEST_UPLOAD_URL=https://secure.ubki.ua:4040/upload/data/xml
```
## Usage
Add `IntegratorUbki`-trait to the model with client data:
```
    use Artjoker\LaravelUbki\Traits\IntegratorUbki;

    class Loan extends Model
    {
        use IntegratorUbki;
        ...
    }
```

Set the necessary the mapping variables in `config/ubki.php`:

```
'model_data' => [
  'okpo'  => 'inn',           // ИНН
  'lname' => 'lastName',      // Фамилия
  'fname' => 'firstName',     // Имя
  'mname' => 'middleName',    // Отчество
  'bdate' => 'birth_date',    // Дата рождения (гггг-мм-дд)
  'dtype' => 'passport_type', // Тип паспорта (см. справочник "Тип документа")
  'dser'  => 'passport_ser',  // Серия паспорта или номер записи ID-карты
  'dnom'  => 'passport_num',  // Номер паспорта или номер ID-карты
  'ctype' => 'contact_type',  // Тип контакта (см. справочник "Тип контакта")
  'cval'  => 'contact_val',   // Значение контакта (например - "+380951111111")
  'foto'  => 'foto',          // <base64(Фото)>
],
```
This map establishes the correspondence between the attributes of your model and the required query fields in UBKI.

Add a new method `ubkiAttributes()` to the class to add the necessary attributes and fill them with data:

```
    use Artjoker\LaravelUbki\Traits\IntegratorUbki;

    class Loan extends Model
    {
        use IntegratorUbki;
        ...
        
        public function ubkiAttributes($params = [])
        {
            $client_data = json_decode($this->attributes['client_data']);
            $this->attributes['inn']        = trim($client_data->code); 
            $this->attributes['lastName']   = trim($client_data->lastName); 
            ...
        }
    }
```
You can use other ways to create custom attributes that you specified in `'model_data'` (`config/ubki.php`).

Now, you can get data from UBKI:
```php
$loan = Loan::find(1); 
$result = $loan->ubki();
```
`$result['response']` - xml response from UBKI (standard report).

You can also pass parameters:
```php
$result = $loan->ubki($params);
```
- `$params['report']` - report alias, if you need other reports; 
- `$params['request_id']` - your request ID (if necessary);
- `$params['lang']` - search language;

You can send the loan data to UBKI:
```php
$result = $loan->ubki_upload($params);
```
`$params` - will be passed to the ubkiAttributes() method in the model.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/artjoker/laravel-ubki.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/artjoker/laravel-ubki.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/artjoker/laravel-ubki
[link-downloads]: https://packagist.org/packages/artjoker/laravel-ubki
[link-ubki]: https://www.ubki.ua/
[link-ubki-api]: https://sites.google.com/ubki.ua/doc/%D0%BE%D0%B1%D1%89%D0%B8%D0%B5-%D0%BF%D1%80%D0%B8%D0%BD%D1%86%D0%B8%D0%BF%D1%8B-%D0%B2%D0%B7%D0%B0%D0%B8%D0%BC%D0%BE%D0%B4%D0%B5%D0%B9%D1%81%D1%82%D0%B2%D0%B8%D1%8F

