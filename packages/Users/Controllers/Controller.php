<?php

namespace PhpSoft\Users\Controllers;

use Auth;
use Validator;
use App\Http\Controllers\Controller as AppController;

class Controller extends AppController
{
    /**
     * Check authentication
     * 
     * @return boolean
     */
    public function checkAuth()
    {
        return !empty(Auth::user());
    }

    /**
     * Check permission
     * 
     * @return boolean
     */
    public function checkPermission($permission)
    {
        return Auth::user()->can($permission) || Auth::user()->hasRole('admin');
    }
    /**
     * validateInput 
     * @param  Validator $validator
     * @param  array $attributes
     * @return boolean
     */
    public function validateInput($validator, $attributes)
    {
        $rules = $validator->getRules();
        $ruleAttributes = array_keys($rules);
        $validateErrors = [];

        foreach ($attributes as $attribute) {
            if (!in_array($attribute, $ruleAttributes)) {
                $validateErrors[] = "$attribute is not allowed change.";
            }
        }

        if (!$validateErrors) {
            return false;
        }

        return $validateErrors;
    }
}
