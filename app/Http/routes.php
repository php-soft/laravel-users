<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/posts', [
    'middleware' => 'permission:create-post',
    function () {
        return response()->json(null, 200);
    }
]);

Route::group(['middleware'=>'routePermission'], function() {

    Route::post('/blog/{id}', function ($id) {
        return response()->json(null, 200);
    });
});

Route::post('/auth/login', '\PhpSoft\Users\Controllers\AuthController@login');
Route::post('/users', '\PhpSoft\Users\Controllers\UserController@create');
Route::group(['middleware'=>'jwt.auth'], function() {

    Route::post('/auth/logout', '\PhpSoft\Users\Controllers\AuthController@logout');
    Route::get('/me', '\PhpSoft\Users\Controllers\UserController@authenticated');
    Route::patch('/me/profile', '\PhpSoft\Users\Controllers\UserController@updateProfile');
    Route::put('/me/password', '\PhpSoft\Users\Controllers\PasswordController@change');
});

Route::post('/passwords/forgot', '\PhpSoft\Users\Controllers\PasswordController@forgot');
Route::post('/passwords/reset', '\PhpSoft\Users\Controllers\PasswordController@reset');

Route::group(['middleware'=>'routePermission'], function() {

    Route::get('/users', '\PhpSoft\Users\Controllers\UserController@index');
});