<?php

namespace PhpSoft\Users\Controllers;

use Auth;
use JWTAuth;
use Validator;
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
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|confirmed|min:6',
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
     * update profile action
     * @param  Request $request
     * @return Response
     */
    public function updateProfile(Request $request)
    {
        if (!$this->checkAuth()) {
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
        ]);
        if ($validator->fails()) {
            return response()->json(arrayView('phpsoft.users::errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        // check attribute invalid
        $rules = $validator->getRules();
        $ruleAttributes = array_keys($rules);
        $requestAttributes = $request->all();
        $requestAttributeKeys = array_keys($requestAttributes);
        foreach ($requestAttributeKeys as $requestAttributeKey) {
            if (!in_array($requestAttributeKey, $ruleAttributes)) {
                return response()->json(null, 400);
            }
        }

        // Update profile
        $user = Auth::user();
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
    public function delete($id)
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

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
}
