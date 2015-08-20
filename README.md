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
    PhpSoft\Illuminate\ArrayView\Providers\ArrayViewServiceProvider::class,
    PhpSoft\Illuminate\Users\Providers\UserServiceProvider::class,
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
$ php artisan vendor:publish --provider="PhpSoft\Illuminate\Users\Providers\UserServiceProvider"
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

Remove middlewares in `app/Http/Kernel.php`

* `\App\Http\Middleware\EncryptCookies::class`
* `\App\Http\Middleware\VerifyCsrfToken::class`

Add route middlewares in `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ...
    'jwt.auth' => \PhpSoft\Illuminate\Users\Middleware\Authenticate::class,
    'jwt.refresh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,
];
```

Add routes in `app/Http/routes.php`

```php
Route::post('/auth/login', '\PhpSoft\Illuminate\Users\Controllers\AuthController@login');
Route::post('/users', '\PhpSoft\Illuminate\Users\Controllers\UserController@create');
Route::group(['middleware'=>'auth'], function() { // use middleware jwt.auth if use JSON Web Token
    Route::post('/auth/logout', '\PhpSoft\Illuminate\Users\Controllers\AuthController@logout');
    Route::get('/me', '\PhpSoft\Illuminate\Users\Controllers\UserController@authenticated');
    Route::put('/me/password', '\PhpSoft\Illuminate\Users\Controllers\UserController@changePassword');
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
use PhpSoft\Illuminate\Users\Models\UserTrait;

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
