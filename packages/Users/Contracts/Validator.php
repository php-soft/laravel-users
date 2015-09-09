<?php

namespace PhpSoft\Users\Contracts;

interface Validator
{
    /**
     * Custom validator rule
     * 
     * @return boolean
     */
    public static function boot();

    /**
     * Declare rules
     * 
     * @return array
     */
    public static function rules();
}
