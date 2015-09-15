<?php

namespace App\Http\Validators;

use Request;
use Validator as IlluminateValidator;
use PhpSoft\Users\Contracts\Validator;

/**
 * User Validate
 *
 * return array
 */
class UserValidate implements Validator
{
    /**
     * Custom validator
     * 
     * @return boolean
     */
    public static function boot($request)
    {

        IlluminateValidator::extend('validate_name', function($attribute, $value, $parameters) {

                return $value == 'validate_name';
            }, 'The name is in valid.'
        );
    }

    /**
     * Declare rules
     * 
     * @return array
     */
    public static function rules()
    {
        return [
            'name'     => 'required|max:255|validate_name',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:6'
        ];
    }
}
