<?php

namespace PhpSoft\Users\Controllers;

use Auth;
use Config;
use Input;
use JWTAuth;
use Validator;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\User;

class UserController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = config('phpsoft.users.model');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function authenticated()
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

        return response()->json(arrayView('phpsoft.users::user/read', [
            'user' => Auth::user()
        ]), 200);
    }

    /**
     * Register validate
     *
     * @param  Request $request
     * @return boolean
     */
    public function registerValidators()
    {
        Validator::extend('changePassword', function ($attribute, $code, $parameters, $validator) {

            $input = $validator->getData();

            if (isset($input['old_password']) && isset($input['password'])) {

                $user = isset($input['id']) ? AppUser::find($input['id']) : Auth::user();
                $checkOldPassword = Auth::attempt(['id' => $user->id, 'password' => $input['old_password']]);

                if ($checkOldPassword) {
                    return true;
                }
            }

            return false;

        }, 'The old password and password are required or the old password is incorrect.');
    }

    /**
     * Create user action
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users',
            'password'   => 'required|string|confirmed|min:6',
            'username'   => 'string|max:30',
            'country'    => 'string|max:100',
            'location'   => 'string|max:100',
            'biography'  => 'string|max:255',
            'occupation' => 'string|max:255',
            'website'    => 'string|max:255',
            'image'      => 'string|max:255',
            'gender'     => 'integer',
            'birthday'   => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $userModel = $this->userModel;
        $user = $userModel::create($request->all());

        return response()->json(arrayView('phpsoft.users::user/read', [
            'user' => $user
        ]), 201);
    }

    /**
     * Update profile action
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id = null)
    {
        // validate
        $this->registerValidators();

        // check auth if update me
        if (!$id && !$this->checkAuth()) {
            return response()->json(null, 401);
        }

        // check user
        $user = $id ? AppUser::find($id) : Auth::user();

        if (!$user) {
            return response()->json(null, 404);
        }

        $request->id = $id;
        // validate data
        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|max:255',
            'email'        => 'sometimes|required|email|max:255|unique:users,email,'.$user->id,
            'old_password' => 'sometimes|required|changePassword|min:6',
            'password'     => 'sometimes|required|changePassword|confirmed|min:6',
            'username'     => 'string|max:30',
            'country'      => 'string|max:100',
            'location'     => 'string|max:100',
            'biography'    => 'string|max:255',
            'occupation'   => 'string|max:255',
            'website'      => 'string|max:255',
            'image'        => 'string|max:255',
            'gender'       => 'integer',
            'birthday'     => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $userModel = $this->userModel;

        // check user
        $user = $id ? $userModel::find($id) : Auth::user();

        if (!$user) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(arrayView('phpsoft.users::user/read', [
            'user' => $user
        ]), 200);
    }

    /**
     * Delete user
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $userModel = $this->userModel;

        // get user by id
        $user = $userModel::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        // delete user
        $deleteUser = $user->delete();

        if (!$deleteUser) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /**
     * View user
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $userModel = $this->userModel;

        // get user by id
        $user = $userModel::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        return response()->json(arrayView('phpsoft.users::user/read', [
            'user' => $user
        ]), 200);
    }

    /**
     * index
     * @return json
     */
    public function index(Request $request)
    {
        $userModel = $this->userModel;

        $users = $userModel::browse([
            'order'     => [ Input::get('sort', 'id') => Input::get('direction', 'desc') ],
            'limit'     => ($limit = (int)Input::get('limit', 25)),
            'cursor'    => Input::get('cursor'),
            'offset'    => (Input::get('page', 1) - 1) * $limit,
            'filters'   => $request->all()
        ]);

        return response()->json(arrayView('phpsoft.users::user/browse', [
            'users' => $users,
        ]), 200);
    }

    /**
     * block user
     *
     * @param  int $id
     * @return json
     */
    public function block($id)
    {
        $userModel = $this->userModel;

        $user = $userModel::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        if ($user->isBlock()) {
            return response()->json(null, 204);
        }

        if (!$user->block()) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /**
     * unblock user
     *
     * @param  int $id
     * @return json
     */
    public function unblock($id)
    {
        $userModel = $this->userModel;

        $user = $userModel::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        if (!$user->isBlock()) {
            return response()->json(null, 204);
        }

        if (!$user->unblock()) {
            return response()->json(null, 500); // @codeCoverageIgnore
        }

        return response()->json(null, 204);
    }

    /*
     * assign role
     * @param  int  $id
     * @param  Request $request
     * @return json
     */
    public function assignRole($id, Request $request)
    {
        $userModel = $this->userModel;

        $user = $userModel::find($id);

        if (!$user) {
            return response()->json(null, 404);
        }

        $roleIdOrName = $request->roleIdOrName;
        $field = is_numeric($roleIdOrName) ? 'id' : 'name';
        $role = Role::where($field, $roleIdOrName)->first();

        if (!$role) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => ['Role does not exist.']
            ]), 400);
        }

        $hasRole = $user->hasRole($role->name);

        if ($hasRole) {
            return response()->json(null, 204);
        }

        $user->attachRole($role);

        return response()->json(null, 204);
    }
}
