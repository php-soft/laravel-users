# Laravel Users Module

[![Build Status](https://travis-ci.org/php-soft/laravel-users.svg)](https://travis-ci.org/php-soft/laravel-users)

> This module is use JWTAuth and ENTRUST libraries
> 
> 1. https://github.com/tymondesigns/jwt-auth (JSON Web Token)
> 2. https://github.com/Zizaco/entrust (Role-based Permissions)

## 1. Installation

Install via composer - edit your `composer.json` to require the package.

```js
"require": {
    // ...
    "php-soft/laravel-users": "dev-master",
}
```

Then run `composer update` in your terminal to pull it in.
Once this has finished, you will need to add the service provider to the `providers` array in your `app.php` config as follows:

```php
'providers' => [
    // ...
    PhpSoft\ArrayView\Providers\ArrayViewServiceProvider::class,
    PhpSoft\Users\Providers\UserServiceProvider::class,
    Tymon\JWTAuth\Providers\JWTAuthServiceProvider::class,
    Zizaco\Entrust\EntrustServiceProvider::class,
]
```

Next, also in the `app.php` config file, under the `aliases` array, you may want to add facades.

```php
'aliases' => [
    // ...
    'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
    'Entrust' => Zizaco\Entrust\EntrustFacade::class,
]
```

You will want to publish the config using the following command:

```sh
$ php artisan vendor:publish --provider="PhpSoft\Users\Providers\UserServiceProvider"
```

***Don't forget to set a secret key in the jwt config file!***

I have included a helper command to generate a key as follows:

```sh
$ php artisan jwt:generate
```

this will generate a new random key, which will be used to sign your tokens.

## 2. Migration and Seeding

Now generate the migration:

```sh
$ php artisan ps-users:migrate
```

It will generate the `<timestamp>_entrust_setup_tables.php` migration. You may now run it with the artisan migrate command:

```sh
$ php artisan migrate
```

Running Seeders with command:

```sh
$ php artisan db:seed --class=UserModuleSeeder
```

## 3. Usage

### 3.1. Authenticate with JSON Web Token

You need to change class `App\User` to inherit from `PhpSoft\Users\Models\User` as follows:

```php
namespace App;

// ...
use PhpSoft\Users\Models\User as PhpSoftUser;

class User extends PhpSoftUser implements AuthenticatableContract, CanResetPasswordContract
{
    // ...

    // You need allows fill attributes as follows
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'location',
        'country',
        'biography',
        'occupation',
        'website',
        'image'
    ];

    // ...
}
``` 

Remove middlewares in `app/Http/Kernel.php`

* `\App\Http\Middleware\EncryptCookies::class`
* `\App\Http\Middleware\VerifyCsrfToken::class`

Add route middlewares in `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ...
    'jwt.auth' => \PhpSoft\Users\Middleware\Authenticate::class,
    'jwt.refresh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,
];
```

Add routes in `app/Http/routes.php`

```php
Route::post('/auth/login', '\PhpSoft\Users\Controllers\AuthController@login');
Route::post('/users', '\PhpSoft\Users\Controllers\UserController@create');
Route::group(['middleware'=>'auth'], function() { // use middleware jwt.auth if use JSON Web Token
    Route::post('/auth/logout', '\PhpSoft\Users\Controllers\AuthController@logout');
    Route::get('/me', '\PhpSoft\Users\Controllers\UserController@authenticated');
    Route::patch('/me/profile', '\PhpSoft\Users\Controllers\UserController@updateProfile');
    Route::put('/me/password', '\PhpSoft\Users\Controllers\PasswordController@change');

});
Route::post('/passwords/forgot', '\PhpSoft\Users\Controllers\PasswordController@forgot');
Route::post('/passwords/reset', '\PhpSoft\Users\Controllers\PasswordController@reset');
Route::group(['middleware'=>'routePermission'], function() {

    Route::get('/users', '\PhpSoft\Users\Controllers\UserController@index');
    Route::get('/users/{id}', '\PhpSoft\Users\Controllers\UserController@show');
    Route::delete('/users/{id}', '\PhpSoft\Users\Controllers\UserController@destroy');
    Route::patch('/users/{id}/profile', '\PhpSoft\Users\Controllers\UserController@update');
});
```

Apache seems to discard the Authorization header if it is not a base64 encoded user/pass combo. So to fix this you can add the following to your apache config

```
RewriteEngine On

RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

Alternatively you can include the token via a query string

```
http://api.mysite.com/me?token={yourtokenhere}
```

### 3.2. Role-based Permissions

Use the `UserTrait` trait in your existing `App\User` model. For example:

```php
namespace App;

use Illuminate\Database\Eloquent\Model;
use PhpSoft\Users\Models\UserTrait;

class User extends Model
{
    use UserTrait; // add this trait to your user model
    // ...
}
```

Create `Role` and `Permission` follows

```php
// create role admin (default this role has been created on UserModuleSeeder)
$admin = new Role();
$admin->name         = 'admin';
$admin->display_name = 'User Administrator'; // optional
$admin->description  = 'User is allowed to manage and edit other users'; // optional
$admin->save();

// role attach alias
$user->attachRole($admin); // parameter can be an Role object, array, or id

// or eloquent's original technique
$user->roles()->attach($admin->id); // id only

// create permission
$createPost = new Permission();
$createPost->name         = 'create-post';
$createPost->display_name = 'Create Posts'; // optional
$createPost->description  = 'create new blog posts'; // optional
$createPost->save();

$admin->attachPermission($createPost);
// equivalent to $admin->perms()->sync(array($createPost->id));
```

Now we can check for roles and permissions simply by doing:

```php
$user->hasRole('owner');   // false
$user->hasRole('admin');   // true
$user->can('edit-user');   // false
$user->can('create-post'); // true
```

Both `hasRole()` and `can()` can receive an array of roles & permissions to check:

```php
$user->hasRole(['owner', 'admin']);       // true
$user->can(['edit-user', 'create-post']); // true
```

### 3.3 Forgot password

To send mail forgot password, 
- You need to add address and name of sender in `config\mail.php` as follows:

```php
'from' => ['address' => 'no-reply@example.com', 'name' => 'System'],
```

- You need to create email view: 
Create  `password.blade.php` file in folder `resources\views\emails` with contents as follows:

```php
<h3>You are receiving this e-mail because you requested resetting your password to domain.com</h3>
Please click this URL to reset your password: <a href="http://domain.com/passwords/reset?token={{$token}}">http://domain.com/passwords/reset?token={{$token}}</a>
```
You can change contents of this view for your using.

By other way, you can use other view and config `password.email` in `config\auth.php`:

```php
    'password' => [
        'email' => 'emails.password',
        'table' => 'password_resets',
        'expire' => 60,
    ],
```

### 3.4 Middlewares

#### PhpSoft\Users\Middleware\Permission

This middleware is use to check permission for an action.

Add route middlewares in app/Http/Kernel.php

```php
protected $routeMiddleware = [
    // ...
    'permission' => \PhpSoft\Users\Middleware\Permission::class,
];
```

Usage

```php
Route::post('/posts', [
    'middleware' => 'permission:create-post', // Only allows user have create-post permission (or have admin role) access to this route
    function () {
        // ...
    }
]);
```

#### PhpSoft\Users\Middleware\RoutePermission

This middleware is use to check permission for a route dynamic by database.

Add route middlewares in app/Http/Kernel.php

```php
protected $routeMiddleware = [
    // ...
    'routePermission' => \PhpSoft\Users\Middleware\RoutePermission::class,
];
```

Usage

```php
Route::group(['middleware'=>'routePermission'], function() { 
    Route::post('/blog', function () {
        //
    });
});
```

Require permission for a route as follows

```php
// require permissions
PhpSoft\Users\Models\RoutePermission::setRoutePermissions('POST /blog', ['create-blog']);

// require roles
PhpSoft\Users\Models\RoutePermission::setRouteRoles('POST /blog', ['creator']);

// require permissions or roles
PhpSoft\Users\Models\RoutePermission::setRoutePermissionsRoles('POST /blog', ['create-blog'], ['creator']);
```
