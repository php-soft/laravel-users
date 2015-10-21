<?php
namespace PhpSoft\Users\Controllers;

use Input;
use Validator;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\Role;

class RoleController extends Controller
{
    /**
     * Create role action
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|max:255|unique:roles',
            'display_name' => 'max:255',
            'description'  => 'max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $role = Role::create($request->all());

        return response()->json(arrayView('phpsoft.users::role/read', [
            'role' => $role
        ]), 201);
    }

    /**
     * Update role action
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id = null)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'name'         => 'max:255|unique:roles,name,'.$id,
            'display_name' => 'max:255',
            'description'  => 'max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        // check role
        $role = Role::find($id);

        if (!$role) {
            return response()->json(null, 404);
        }

        // update role
        $updateRole = $role->update($request->all());

        if (!$updateRole) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(arrayView('phpsoft.users::role/read', [
            'role' => $role
        ]), 200);
    }

    /**
     * Delete role
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        // get role by id
        $role = Role::find($id);

        if (!$role) {
            return response()->json(null, 404);
        }

        // delete role
        $deleteRole = $role->delete();

        if (!$deleteRole) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /**
     * View role
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        // get role by id
        $role = Role::find($id);

        if (!$role) {
            return response()->json(null, 404);
        }

        return response()->json(arrayView('phpsoft.users::role/read', [
            'role' => $role
        ]), 200);
    }

    /**
     * index
     * @return json
     */
    public function index(Request $request)
    {
        $roles = Role::browse([
            'order'     => [ Input::get('sort', 'id') => Input::get('direction', 'desc') ],
            'limit'     => ($limit = (int)Input::get('limit', 25)),
            'offset'    => (Input::get('page', 1) - 1) * $limit,
            'filters'   => $request->all()
        ]);

        return response()->json(arrayView('phpsoft.users::role/browse', [
            'roles' => $roles,
        ]), 200);
    }

    /**
     * index
     * @param  int $id
     * @return json
     */
    public function indexByUser($id)
    {
        $user = \App\User::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        $roles = Role::browseByUser([
            'order'     => [ Input::get('sort', 'name') => Input::get('direction', 'asc') ],
            'limit'     => ($limit = (int)Input::get('limit', 25)),
            'offset'    => (Input::get('page', 1) - 1) * $limit,
            'user'      => $user
        ]);

        return response()->json(arrayView('phpsoft.users::role/browse', [
            'roles' => $roles,
        ]), 200);
    }
}
