<?php

namespace PhpSoft\Users\Middleware;

use Closure;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Exceptions\JWTException;
use Zizaco\Entrust\EntrustFacade as Entrust;
use PhpSoft\Users\Models\RoutePermission as RoutePermissionModel;

class RoutePermission
{
    /**
     * The JWTAuth implementation.
     *
     * @var JWTAuth
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
     * @param  JWTAuth  $auth
     * @return void
     */
    public function __construct(JWTAuth $auth, Router $router)
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
            if (($user = $this->user($request)) === 401) {
                return response()->json(null, 401);
            }

            $hasRole  = $user->hasRole($routePermission->roles, false);
            $hasPerms = $user->can($routePermission->permissions, false);

            $hasRolePerm = $hasRole || $hasPerms;

            if (!$hasRolePerm) {
                return response()->json(null, 403);
            }
        }

        return $next($request);
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    protected function user($request)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return 401;
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (JWTException $e) {
            return 401;
        }

        if (!$user) {
            return 401;
        }

        return $user;
    }
}
