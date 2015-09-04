<?php

namespace PhpSoft\Users\Controllers;

use Input;
use Auth;
use JWTAuth;
use Validator;
use App\User as AppUser;
use Illuminate\Http\Request;
use PhpSoft\Users\Models\User;

class UserController extends Controller
{
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
     * Create user action
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|confirmed|min:6',
            'username'   => 'max:30',
            'country'    => 'max:100',
            'location'   => 'max:100',
            'biography'  => 'max:255',
            'occupation' => 'max:255',
            'website'    => 'max:255',
            'image'      => 'max:255',
            'gender'     => 'Integer',
            'birthday'   => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $user = User::create($request->all());

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
        // check auth if update me
        if (!$id && !$this->checkAuth()) {
            return response()->json(null, 401);
        }

        // validate data
        $validator = Validator::make($request->all(), [
            'name'       => 'max:255',
            'username'   => 'max:30',
            'country'    => 'max:100',
            'location'   => 'max:100',
            'biography'  => 'max:255',
            'occupation' => 'max:255',
            'website'    => 'max:255',
            'image'      => 'max:255',
            'gender'     => 'Integer',
            'birthday'   => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        // check attribute invalid
        $rules = $validator->getRules();
        $requestAttributes = $request->all();
        $validatorErrors = $this->validateInput($rules, $requestAttributes);

        if ($validatorErrors) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validatorErrors
            ]), 400);
        }

        // check user
        $user = $id ? User::find($id) : Auth::user();

        // Update profile
        if (!$user) {
            return response()->json(null, 404);
        }

        $updateProfile = $user->update($requestAttributes);
        
        if (!$updateProfile) {
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
        // get user by id
        $user = User::find($id);

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
        // get user by id
        $user = User::find($id);

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
        $users = AppUser::browse([
            'order'     => [ 'id' => 'desc' ],
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
        $user = User::find($id);

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
        $user = User::find($id);

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
}
