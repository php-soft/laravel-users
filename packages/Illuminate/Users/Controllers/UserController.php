<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use JWTAuth;

class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function authenticatedUser()
    {
        return response()->json(\Auth::user(), 200);
    }
}
