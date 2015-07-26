<?php

namespace PhpSoft\Illuminate\Users\Controllers;

use Input;
use Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Authenticate
 */
class AuthController extends Controller
{
    /**
     * Login action
     * 
     * @return json
     */
    public function login()
    {
        // grab credentials from the request
        $credentials = Input::only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(arrayView('errors/authenticate', ['error' => 'Invalid Credentials.']), 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(arrayView('errors/authenticate', ['error' => 'Could not create token.']), 500);
        }

        // all good so return the token
        return response()->json(arrayView('tokens/show', compact('token')));
    }

    /**
     * Logout action
     * 
     * @return Response
     */
    public function logout()
    {
        if (!$this->checkAuth()) {
            return response()->json(null, 401);
        }

        Auth::logout();
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(null, 204);
    }
}
