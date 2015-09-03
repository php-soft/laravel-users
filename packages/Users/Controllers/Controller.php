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
     * Validate Input 
     * @param  array $ruleValidators
     * @param  array $requestAttributes
     * @return $validateErrors
     */
    public function validateInput($ruleValidators, $requestAttributes)
    {
        $ruleAttributes = array_keys($ruleValidators);
        $requestAttributeKeys = array_keys($requestAttributes);
        $validateErrors = [];

        foreach ($requestAttributeKeys as $requestAttributeKey) {
            if (!in_array($requestAttributeKey, $ruleAttributes)) {
                $validateErrors[] = "The $requestAttributeKey can not be changed.";
            }
        }

        return $validateErrors;
    }
}
