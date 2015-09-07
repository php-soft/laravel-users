<?php

namespace App\Http\Validators;

use PhpSoft\Users\Contracts\Validator;

/**
 * User Validate
 *
 * return array
 */
class UserValidate implements Validator
{
    public static function rules()
    {
        return [
            'name'     => 'required|max:255',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:6'
        ];
    }
}
