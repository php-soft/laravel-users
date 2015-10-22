<?php
namespace PhpSoft\Users\Controllers;

use Auth;
use Input;
use Route;
use Validator;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\Permission;
use PhpSoft\Users\Models\RoutePermission;

class RoutePermissionController extends Controller
{
    public function registerValidators()
    {
        Validator::extend('rolePermission', function ($attribute, $array) {

            $flag = true;

            if (is_array($array)) {
                foreach ($array as $value) {
                    if ($attribute == "roles") {
                        $check = Role::where('name', $value);
                    } else {
                        $check = Permission::where('name', $value);
                    }
                    if (!$check->count()) {
                        $flag = false;
                        break;
                    }
                }
            } else {
                $flag = false;
            }

            return $flag;

        }, 'Roles or permissions are invalid.');
    }

    /**
     * Create route permission action
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // validate
        $this->registerValidators();

        $validator = Validator::make($request->all(), [
            'route'         => 'required|max:255|string|unique:route_permission,route',
            'permissions'   => 'required|max:255|array|rolePermission',
            'roles'         => 'required|max:255|array|rolePermission'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        // check current user is admin
        if (!(Auth::user() && Auth::user()->hasRole('admin'))) {
            return response()->json(null, 403);
        }

        // add permissions and roles for the route
        $routePermission = RoutePermission::setRoutePermissionsRoles(
            $request['route'],
            $request['permissions'],
            $request['roles']
        );

        return response()->json(arrayView('phpsoft.users::routePermission/read', [
            'routePermission' => $routePermission
        ]), 201);
    }

    /**
     * Update permissions and roles for a route
     *
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        $routePermission = RoutePermission::find($id);

        if ($routePermission == null) {
            return response()->json(null, 404);
        }

        // validate
        $this->registerValidators();

        $validator = Validator::make($request->all(), [
            'route'         => 'sometimes|required|string|max:255|unique:route_permission,route,'.$id,
            'permissions'   => 'sometimes|required|array|max:255|rolePermission',
            'roles'         => 'sometimes|required|array|max:255|rolePermission'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $request['permissions'] = isset($request['permissions'])?
        json_encode($request['permissions']) : $routePermission->permissions;
        $request['roles'] = isset($request['roles']) ?
        json_encode($request['roles']) : $routePermission->roles;

        // check current user is admin
        if (!(Auth::user() && Auth::user()->hasRole('admin'))) {
            return response()->json(null, 403);
        }

        // update permissions and roles for the route
        $routePermission = $routePermission->update($request->all());

        return response()->json(arrayView('phpsoft.users::routePermission/read', [
            'routePermission' => $routePermission
        ]), 200);
    }

    /**
     * Delete route permission
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        // get route permission by id
        $routePermission = RoutePermission::find($id);

        if (!$routePermission) {
            return response()->json(null, 404);
        }

        // check current user is admin
        if (!(Auth::user() && Auth::user()->hasRole('admin'))) {
            return response()->json(null, 403);
        }

        // delete route permission
        $deleteRoutePermission = $routePermission->delete();

        if (!$deleteRoutePermission) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /**
     * View route permission
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        // get permissions and roles of a route by id
        $routePermission = RoutePermission::find($id);

        if (!$routePermission) {
            return response()->json(null, 404);
        }

        return response()->json(arrayView('phpsoft.users::routePermission/read', [
            'routePermission' => $routePermission
        ]), 200);
    }

    /**
     * index
     * @return json
     */
    public function index(Request $request)
    {
        $routePermissions = RoutePermission::browse([
            'order'     => [ Input::get('sort', 'id') => Input::get('direction', 'desc') ],
            'limit'     => ($limit = (int)Input::get('limit', 25)),
            'offset'    => (Input::get('page', 1) - 1) * $limit,
            'filters'   => $request->all()
        ]);

        return response()->json(arrayView('phpsoft.users::routePermission/browse', [
            'routePermissions' => $routePermissions,
        ]), 200);
    }

	/**
     * List all routes in app
     *
     * @param
     * @return Response
     */
    public function getAllRoutes()
    {
        $routes = Route::getRoutes();
        $results = [];

        if ($routes != null) {
            foreach ($routes as $route) {
                $route = array(
                    'method' => $route->getMethods(),
                    'uri'    => $route->getPath()
                );
                $results[] = (object)$route;
            }
        }

        return response()->json(arrayView('phpsoft.users::partials/route', [
            'routes' => $results,
        ]), 200);
    }
}
