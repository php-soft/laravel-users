<?php

use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\Permission;
use PhpSoft\Users\Models\RoutePermission;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class RoutePermissionControllerTest extends TestCase
{
    use WithoutMiddleware;

    public function testCreateValidateFailure()
    {
        $this->withoutMiddleware();

        // test params are empty
        $res = $this->call('POST', '/routePermissions', []);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('route', $results->errors);
        $this->assertObjectHasAttribute('roles', $results->errors);
        $this->assertObjectHasAttribute('permissions', $results->errors);
        $this->assertEquals('The route field is required.', $results->errors->route[0]);
        $this->assertEquals('The permissions field is required.', $results->errors->permissions[0]);
        $this->assertEquals('The roles field is required.', $results->errors->roles[0]);

        // test params are incorrect
        $res = $this->call('POST', '/routePermissions', [
            'route'       => 1,
            'permissions' => 1,
            'roles'       => 1
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('route', $results->errors);
        $this->assertObjectHasAttribute('roles', $results->errors);
        $this->assertObjectHasAttribute('permissions', $results->errors);
        $this->assertEquals('The route must be a string.', $results->errors->route[0]);
        $this->assertEquals('The permissions must be an array.', $results->errors->permissions[0]);
        $this->assertEquals('The roles must be an array.', $results->errors->roles[0]);

        $res = $this->call('POST', '/routePermissions', [
            'route'       => '/users',
            'permissions' => ['create-post', 'review'],
            'roles'       => ['admin', 'manager']
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('roles', $results->errors);
        $this->assertObjectHasAttribute('permissions', $results->errors);
        $this->assertEquals('Roles or permissions are invalid.', $results->errors->permissions[0]);
        $this->assertEquals('Roles or permissions are invalid.', $results->errors->roles[0]);

        // test add permissions and roles for route that has existed already
        $routePermission = factory(RoutePermission::class)->create(['route' => '/users']);
        $res = $this->call('POST', '/routePermissions', [
            'route'       => '/users',
            'permissions' => ['create-post', 'review'],
            'roles'       => ['admin', 'manager']
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('route', $results->errors);
        $this->assertEquals('The route has already been taken.', $results->errors->route[0]);

        // test current user isn't admin
        $user = factory(App\User::class)->create();
        Auth::login($user);
        $permission = factory(Permission::class)->create(['name' => 'create-post']);

        $res = $this->call('POST', '/routePermissions', [
            'route'       => '/trackings',
            'permissions' => ['create-post'],
            'roles'       => ['admin']
        ]);
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testCreateSuccess()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $role = factory(Role::class)->create(['name' => 'manager']);
        $permission = factory(Permission::class)->create(['name' => 'create-post']);
        $permission1 = factory(Permission::class)->create(['name' => 'review']);

        $res = $this->call('POST', '/routePermissions', [
            'route'       => '/users',
            'permissions' => ['create-post', 'review'],
            'roles'       => ['admin', 'manager']
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);

        $this->assertEquals(201, $res->getStatusCode());
        $results = json_decode($res->getContent());

        $this->assertObjectHasAttribute('entities', $results);

        $this->assertInternalType('array', $results->entities);
        $this->assertEquals('/users', $results->entities[0]->route);
        $this->assertEquals(['create-post', 'review'], $results->entities[0]->permissions);
        $this->assertEquals(['admin', 'manager'], $results->entities[0]->roles);
    }

    public function testReadNotFound()
    {
        $res = $this->call('GET', '/routePermissions/1');

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testReadFound()
    {
        $routePermissions = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['creat-post']),
            'roles'       => json_encode(['manager'])
        ]);

        $res = $this->call('GET', '/routePermissions/'.$routePermissions->id);

        $this->assertEquals(200, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('entities', $results);
        $this->assertInternalType('array', $results->entities);
        $this->assertEquals($routePermissions->route, $results->entities[0]->route);
        $this->assertEquals(json_decode($routePermissions->roles), $results->entities[0]->roles);
        $this->assertEquals(json_decode($routePermissions->permissions), $results->entities[0]->permissions);
    }

    public function testUpdateNotExists()
    {
        $res = $this->call('PATCH', '/routePermissions/1', [
            'route' => '/trackings',
        ]);
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testUpdateValidateFailure()
    {
        $routePermissions = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['review']),
            'roles'       => json_encode(['manager'])
        ]);
        $routePermissions1 = factory(RoutePermission::class)->create([
            'route'       => '/trackings',
            'permissions' => json_encode(['creat-post']),
            'roles'       => json_encode(['admin'])
        ]);

        // test params is empty
        $res = $this->call('PATCH', '/routePermissions/'.$routePermissions->id, [
            'route'       => '',
            'permissions' => '',
            'roles'       => ''
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The route field is required.', $results->errors->route[0]);
        $this->assertEquals('The roles field is required.', $results->errors->roles[0]);
        $this->assertEquals('The permissions field is required.', $results->errors->permissions[0]);

        // test update become other record
        $res = $this->call('PATCH', '/routePermissions/'.$routePermissions1->id, [
            'route' => $routePermissions->route,
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The route has already been taken.', $results->errors->route[0]);

        // test role or permission doesn't exist
        $res = $this->call('PATCH', '/routePermissions/'.$routePermissions->id, [
            'permissions' => ['create'],
            'roles'       => ['staff']
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Roles or permissions are invalid.', $results->errors->roles[0]);
        $this->assertEquals('Roles or permissions are invalid.', $results->errors->permissions[0]);

        // test current user isn't admin
        $this->withoutMiddleware();

        $user = factory(App\User::class)->create();
        Auth::login($user);

        $permission = factory(Permission::class)->create(['name' => 'create-post']);

        $res = $this->call('PATCH', '/routePermissions/'.$routePermissions->id, [
            'permissions' => ['create-post'],
        ]);
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testUpdateNothingChange()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $routePermissions = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['review']),
            'roles'       => json_encode(['manager'])
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);

        $res = $this->call('PATCH', '/routePermissions/' . $routePermissions->id);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals($routePermissions->route, $results->entities[0]->route);
        $this->assertEquals(json_decode($routePermissions->permissions), $results->entities[0]->permissions);
        $this->assertEquals(json_decode($routePermissions->roles), $results->entities[0]->roles);
    }

    public function testUpdateWithNewInformation()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $role = factory(Role::class)->create(['name' => 'manager']);
        $permission = factory(Permission::class)->create(['name' => 'create-post']);
        $routePermission = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['review']),
            'roles'       => json_encode(['manager'])
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);

        $permissions = [$permission->name];
        $roles = [$role->name];

        $res = $this->call('PATCH', '/routePermissions/' . $routePermission->id, [
            'permissions' => $permissions,
            'roles'       => $roles,
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals($permissions, $results->entities[0]->permissions);
        $this->assertEquals($roles, $results->entities[0]->roles);
    }

    public function testDeleteNotFound()
    {
        $res = $this->call('DELETE', '/routePermissions/1');
        $this->assertEquals(404, $res->getStatusCode());

        // test current user isn't admin
        $this->withoutMiddleware();

        $user = factory(App\User::class)->create();
        Auth::login($user);

        $routePermission = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['review']),
            'roles'       => json_encode(['manager'])
        ]);

        $res = $this->call('DELETE', '/routePermissions/'.$routePermission->id);
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testDeleteSuccess()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $routePermission = factory(RoutePermission::class)->create([
            'route'       => '/users',
            'permissions' => json_encode(['review']),
            'roles'       => json_encode(['manager'])
        ]);

        $res = $this->call('DELETE', "/routePermissions/{$routePermission->id}", [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(204, $res->getStatusCode());

        $exists = RoutePermission::find($routePermission->id);
        $this->assertNull($exists);
    }

    public function testBrowseNotFound()
    {
        $res = $this->call('GET', '/routePermissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));
    }

    public function testBrowseFound()
    {
        $routePermissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $routePermissions[] = factory(RoutePermission::class)->create();
        }

        $routePermissions = RoutePermission::select('*')->orderBy('id', 'desc')->get();

        $res = $this->call('GET', '/routePermissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(count($routePermissions), count($results->entities));
        for ($i = 0; $i < 10; ++$i) {
            $this->assertEquals($routePermissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderWrongParams()
    {
        $routePermissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $routePermissions[] = factory(RoutePermission::class)->create();
        }

        $routePermissions = RoutePermission::select('*')->orderBy('id', 'desc')->get();

        //check order route permission with emty params
        $res = $this->call('GET', '/routePermissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissions); ++$i) {
            $this->assertEquals($routePermissions[$i]->id, $results->entities[$i]->id);
        }

        // check order route permissions with wrong params
        $res = $this->call('GET', '/routePermissions?sort=sort&direction=direction');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissions); ++$i) {
            $this->assertEquals($routePermissions[$i]->id, $results->entities[$i]->id);
        }

        // check order route permission with the input doesn't has sort
        $res = $this->call('GET', '/routePermissions?direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissions); ++$i) {
            $this->assertEquals($routePermissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderRightParams()
    {
        $routePermissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $routePermissions[] = factory(RoutePermission::class)->create([
                'route'       => 'Route '.$i,
                'permissions' => json_encode(['permissions'.$i]),
                'roles'       => json_encode(['roles'.$i])
            ]);
        }
        $routePermissionsID = RoutePermission::select('*')->orderBy('id', 'desc')->get();
        $routePermissionsRoute = RoutePermission::select('*')->orderBy('permissions', 'desc')->get();
        $routePermissionsPermissions = RoutePermission::select('*')->orderBy('roles', 'desc')->get();

        // check order route permissions with full input
        $res = $this->call('GET', '/routePermissions?sort=route&direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissionsRoute); ++$i) {
            $this->assertEquals($routePermissionsRoute[$i]->id, $results->entities[$i]->id);
        }

        $res = $this->call('GET', '/routePermissions?sort=route&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissionsRoute); ++$i) {
            $this->assertEquals($routePermissionsRoute[9 - $i]->id, $results->entities[$i]->id);
        }

        // check order route permission with other fields
        $res = $this->call('GET', '/routePermissions?sort=permissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($routePermissionsPermissions); ++$i) {
            $this->assertEquals($routePermissionsPermissions[$i]->id, $results->entities[$i]->id);
        }

        // check order route permissions with equals value of order field, route permission is sorted follow id field with desc
        $routePermissions = [];
        for ($i = 0; $i < 10; ++$i) {
            if(in_array($i, [2,4,6])) {
                $routePermissions[] = factory(RoutePermission::class)->create([
                    'permissions' => json_encode(['permissions']),
                    'roles'       => json_encode(['roles'.$i])
                ]);
            }
            $routePermissions[] = factory(RoutePermission::class)->create([
                'permissions' => json_encode(['permissions'.$i]),
                'roles'       => json_encode(['roles'.$i])
            ]);
        }

        $routePermissions1 = RoutePermission::where('route', '=', ['permissions'])
            ->orderBy('id', 'desc')->get();
        $routePermissions2 = RoutePermission::where('route', '<>', ['permissions'])
            ->orderBy('order', 'asc')->get();
        $routePermissions = array_merge((array) $routePermissions1, (array) $routePermissions2);

        $res = $this->call('GET', '/routePermissions?sort=permissions&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 1; $i < count($routePermissions); ++$i) {
            $this->assertEquals($routePermissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithPagination()
    {
        $routePermissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $routePermissions[] = factory(RoutePermission::class)->create();
        }

        // 5 items first
        $res = $this->call('GET', '/routePermissions?limit=5');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($routePermissions[9 - $i]->id, $results->entities[$i]->id);
        }

        // 5 items next
        $nextLink = '/routePermissions?limit=5&page=2';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($routePermissions[4 - $i]->id, $results->entities[$i]->id);
        }

        // over list
        $nextLink = '/routePermissions?limit=5&page=3';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));
    }

    public function testGetAllRoutes()
    {
        $res = $this->call('GET', '/routes');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('method', $results->entities[0]);
        $this->assertObjectHasAttribute('uri', $results->entities[0]);
    }
}
