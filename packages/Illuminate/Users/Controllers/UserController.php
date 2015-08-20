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
     * @param  int  $id
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
     * update profile action
     * @param  Request $request
     * @return Response
     */
    public function updateProfile(Request $request)
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'max:255', 
        ]);

        if ($validator->fails()) {
            return response()->json(arrayView('errors/validation', [
                'errors' => $validator->errors()
            ]), 400);
        }

        $attributes = $request->all();

        foreach ($attributes as $attribute => $value) {
            if ($value == null) {
                unset($attributes[$attribute]);
            }
        }

        $id = Auth::id();
        $user = User::find($id);
        $updateProfile = $user->update($attributes);

        if (!$updateProfile) {
            return response()->json(null, 500);
        }

        return response()->json(arrayView('user/read', [
            'user' => User::find($id)
        ]), 200);
    }
}
