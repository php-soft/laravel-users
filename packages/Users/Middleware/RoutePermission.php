<?php

namespace PhpSoft\Users\Middleware;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
use Zizaco\Entrust\EntrustFacade as Entrust;
use PhpSoft\Users\Models\RoutePermission as RoutePermissionModel;

class RoutePermission
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth, Router $router)
    {
        $this->auth = $auth;
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->current()->methods()[0] . ' /' . $this->router->current()->uri();
        $routePermission = RoutePermissionModel::getRoutePermissionsRoles($route);

        if ($routePermission) {
            if (!$this->user()) {
                return response()->json(null, 401);
            }

            $hasRole  = $this->hasRole($routePermission->roles);
            $hasPerms = $this->can($routePermission->permissions);

            $hasRolePerm = $hasRole || $hasPerms;

            if (!$hasRolePerm) {
                return response()->json(null, 403);
            }
        }

        return $next($request);
    }

    /**
     * Checks if the current user has a role by its name
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    protected function hasRole($role, $requireAll = false)
    {
        return $this->user()->hasRole($role, $requireAll);
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    protected function can($permission, $requireAll = false)
    {
        return $this->user()->can($permission, $requireAll);
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    protected function user()
    {
        return $this->auth->user();
    }
}
