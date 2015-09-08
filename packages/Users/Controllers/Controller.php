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
}
