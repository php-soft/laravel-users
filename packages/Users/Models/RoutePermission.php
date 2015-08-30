<?php

namespace PhpSoft\Users\Models;

use Illuminate\Database\Eloquent\Model;

class RoutePermission extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'route_permission';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['route', 'permissions', 'roles'];

    /**
     * Set route permissions
     *
     * @param string
     * @param array
     */
    public static function setRoutePermissions($route, $permissions = [])
    {
        return self::setRoutePermissionsRoles($route, $permissions, []);
    }

    /**
     * Set route roles
     *
     * @param string
     * @param array
     */
    public static function setRouteRoles($route, $roles = [])
    {
        return self::setRoutePermissionsRoles($route, [], $roles);
    }

    /**
     * Set route permissions and roles
     *
     * @param string
     * @param array
     */
    public static function setRoutePermissionsRoles($route, $permissions = [], $roles = [])
    {
        $routePermission = parent::firstOrNew(['route' => $route]);

        if (count($permissions)) {
            $routePermission->permissions = json_encode($permissions);
        }

        if (count($roles)) {
            $routePermission->roles = json_encode($roles);
        }

        return $routePermission->save();
    }

    /**
     * Get permissions and roles of an route
     *
     * @param  string
     * @return RoutePermission
     */
    public static function getRoutePermissionsRoles($route)
    {
        $routePermission = parent::where('route', $route)->first();

        if (empty($routePermission)) {
            return null;
        }

        $routePermission->permissions = json_decode($routePermission->permissions);
        $routePermission->roles = json_decode($routePermission->roles);
        return $routePermission;
    }
}
