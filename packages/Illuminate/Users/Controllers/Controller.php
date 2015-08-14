<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use Auth;
use PhpSoft\Illuminate\ArrayView\Facades\ArrayView;
use App\Http\Controllers\Controller as AppController;

class Controller extends AppController
{
    /**
     * Instantiate a new Controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        ArrayView::setViewPaths([ __DIR__ . '/../resources/views' ]);
    }

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
