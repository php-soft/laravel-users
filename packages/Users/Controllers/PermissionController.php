<?php
namespace PhpSoft\Users\Controllers;

use Input;
use Validator;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Create permission action
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255|unique:permissions',
            'display_name' => 'string|max:255',
            'description'  => 'max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $permission = Permission::create($request->all());

        return response()->json(arrayView('phpsoft.users::permission/read', [
            'permission' => $permission
        ]), 201);
    }

    /**
     * Update permission action
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id = null)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:255|unique:permissions,name,'.$id,
            'display_name' => 'string|max:255',
            'description'  => 'max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        // check permission
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(null, 404);
        }

        // update permission
        $updatePermission = $permission->update($request->all());

        if (!$updatePermission) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(arrayView('phpsoft.users::permission/read', [
            'permission' => $permission
        ]), 200);
    }

    /**
     * Delete permission
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        // get permission by id
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(null, 404);
        }

        // delete permission
        $deletePermission = $permission->delete();

        if (!$deletePermission) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /**
     * View permission
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        // get permission by id
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(null, 404);
        }

        return response()->json(arrayView('phpsoft.users::permission/read', [
            'permission' => $permission
        ]), 200);
    }

    /**
     * index
     * @return json
     */
    public function index(Request $request)
    {
        $permissions = Permission::browse([
            'order'     => [ Input::get('sort', 'id') => Input::get('direction', 'desc') ],
            'limit'     => ($limit = (int)Input::get('limit', 25)),
            'offset'    => (Input::get('page', 1) - 1) * $limit,
            'filters'   => $request->all()
        ]);

        return response()->json(arrayView('phpsoft.users::permission/browse', [
            'permissions' => $permissions,
        ]), 200);
    }
}
