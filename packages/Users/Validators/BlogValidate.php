<?php

namespace PhpSoft\Users\Validators;

use PhpSoft\Users\Contracts\Validator;

/**
 * Blog Validate
 *
 * return array
 */
class BlogValidate implements Validator
{
    public static function rules()
    {
        return [
            'title'       => 'required|max:255',
            'contents'    => 'required',
            'image'       => 'required'
        ];
    }
}
