<?php

namespace PhpSoft\Users\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class Permission
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permission = 'manage', $role = 'admin')
    {
        if (($status = $this->checkPermission($permission, $role)) !== true) {
            return response()->json(null, $status);
        }

        return $next($request);
    }

    /**
     * Check permission
     * 
     * @return boolean
     */
    protected function checkPermission($permission = 'manage', $role = 'admin')
    {
        if ($this->auth->guest()) {
            return 401;
        }

        if ($this->auth->user()->can($permission) || $this->auth->user()->hasRole($role)) {
            return true;
        }

        return 403;
    }
}
