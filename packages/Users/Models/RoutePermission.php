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

        $routePermission->save();

        return $routePermission;
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

    /**
     * Update permissions and roles of a route.
     *
     * @param  array  $attributes
     * @return bool|int
     */
    public function update(array $attributes = [])
    {
        if (!parent::update($attributes)) {
            throw new Exception('Cannot update category.'); // @codeCoverageIgnore
        }

        return $this->fresh();
    }

    /**
     * List permissions and roles of all route
     *
     * @param  array  $options
     * @return array
     */
    public static function browse($options = [])
    {
        $find = new RoutePermission();
        $fillable = $find->fillable;

        if (!empty($options['filters'])) {
            $inFilters = array_intersect($fillable, array_keys($options['filters']));

            if (!empty($inFilters)) {
                foreach ($inFilters as $key) {
                    $find = ($options['filters'][$key] == null) ? $find : $find->where($key, 'LIKE', '%'. $options['filters'][$key] .'%');
                }
            }
        }

        $total = $find->count();

        if (!empty($options['order'])) {
            foreach ($options['order'] as $field => $direction) {
                if (in_array($field, $fillable)) {
                    $find = $find->orderBy($field, $direction);
                }
            }
        }

        $find = $find->orderBy('id', 'DESC');

        if (!empty($options['offset'])) {
            $find = $find->skip($options['offset']);
        }

        if (!empty($options['limit'])) {
            $find = $find->take($options['limit']);
        }

        return [
            'total'  => $total,
            'offset' => empty($options['offset']) ? 0 : $options['offset'],
            'limit'  => empty($options['limit']) ? 0 : $options['limit'],
            'data'   => $find->get(),
        ];
    }
}
