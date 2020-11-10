<?php

    namespace Artjoker\LaravelUbki\Models;

    use Illuminate\Database\Eloquent\Model;

    class UbkiToken extends Model
    {
        protected $fillable = ['token', 'error_code', 'response', 'account_login'];
    }
