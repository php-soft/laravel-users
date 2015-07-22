<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use Auth;
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
        parent::__construct();

        $this->middleware('jwt.auth');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function authenticated()
    {
        return response()->json(arrayView('user/read', [
            'user' => Auth::user()
        ]), 200);
    }
}
