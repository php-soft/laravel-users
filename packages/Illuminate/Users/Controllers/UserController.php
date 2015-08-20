<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use Auth;
use JWTAuth;
use Validator;
use Illuminate\Http\Request;
use PhpSoft\Illuminate\Users\Models\User;

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

        return response()->json(arrayView('user/read', [
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
            return response()->json(arrayView('errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $user = User::create($request->all());

        return response()->json(arrayView('user/read', [
            'user' => $user
        ]), 201);
    }

    /**
     * Change password
     *
     * @param  Request $request
     * @return Response
     */
    public function changePassword(Request $request)
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|min:6',
            'password'     => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $user = User::find(Auth::user()->id);
        $checkPassword = Auth::attempt(['id' => $user['id'], 'password' => $request['old_password']]);
        if (!$checkPassword) {
            return response()->json(arrayView('errors/validation', [
                'errors' => ['Old password is incorrect.']
            ]), 401);
        }

        $change = $user->changePassword($request['password']);

        if (!$change) {
            return response()->json(null, 500);
        }

        return response()->json(null, 204); 
    }
}
