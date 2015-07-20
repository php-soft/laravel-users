<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use JWTAuth;

class UserController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function authenticatedUser()
    {
        $user = JWTAuth::parseToken()->authenticate();

        dd($user);

        return response()->json(null, 200);
    }
}
