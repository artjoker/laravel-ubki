<?php

    namespace Vt2\LaravelUbki\Models;

    use Illuminate\Database\Eloquent\Model;

    class UbkiToken extends Model
    {
        protected $fillable = ['token', 'error_code', 'response'];
    }
