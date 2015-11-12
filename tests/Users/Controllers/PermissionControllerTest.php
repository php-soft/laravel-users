<?php

use PhpSoft\Users\Models\Permission;

class PermissionControllerTest extends TestCase
{
    public function testCreateValidateFailure()
    {

        $res = $this->call('POST', '/permissions', []);
        $this->assertEquals(400, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name field is required.', $results->errors->name[0]);

        $res = $this->call('POST', '/permissions', [
            'name'         => '',
        ]);

        $this->assertEquals(400, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name field is required.', $results->errors->name[0]);
    }

    public function testCreateSuccess()
    {
        $res = $this->call('POST', '/permissions', [
            'name'         => 'Manager',
        ]);

        $this->assertEquals(201, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('entities', $results);
        $this->assertInternalType('array', $results->entities);
        $this->assertEquals('Manager', $results->entities[0]->name);
        $this->assertEquals(null, $results->entities[0]->description);
    }

    public function testReadNotFound()
    {
        $res = $this->call('GET', '/permissions/0');

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testReadFound()
    {
        $permission = factory(Permission::class)->create();

        $res = $this->call('GET', '/permissions/' . $permission->id);

        $this->assertEquals(200, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('entities', $results);
        $this->assertInternalType('array', $results->entities);
        $this->assertEquals($permission->name, $results->entities[0]->name);
        $this->assertEquals($permission->display_name, $results->entities[0]->display_name);
        $this->assertEquals($permission->description, $results->entities[0]->description);
    }

    public function testUpdateNotExists()
    {
        $res = $this->call('PATCH', '/permissions/0', [
            'name' => 'Manager',
        ]);
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testUpdateValidateFailure()
    {
        $permission = factory(Permission::class)->create();
        $permission1 = factory(Permission::class)->create();

        $res = $this->call('PATCH', '/permissions/' . $permission1->id, [
            'name' => $permission->name,
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The name has already been taken.', $results->errors->name[0]);

        $res = $this->call('PATCH', '/permissions/' . $permission1->id, [
            'name'         => '',
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The name field is required.', $results->errors->name[0]);
    }

    public function testUpdateNothingChange()
    {
        $permission = factory(Permission::class)->create();

        $res = $this->call('PATCH', '/permissions/' . $permission->id);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals($permission->name, $results->entities[0]->name);
        $this->assertEquals($permission->display_name, $results->entities[0]->display_name);
        $this->assertEquals($permission->description, $results->entities[0]->description);
    }

    public function testUpdateWithNewInformation()
    {
        $permission = factory(Permission::class)->create();

        $res = $this->call('PATCH', '/permissions/' . $permission->id, [
            'name'         => 'Manager',
            'display_name' => 'Manager',
            'description'  => 'Manage staff',
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Manager', $results->entities[0]->name);
        $this->assertEquals('Manager', $results->entities[0]->display_name);
        $this->assertEquals('Manage staff', $results->entities[0]->description);
    }

    public function testDeleteNotFound()
    {
        $res = $this->call('DELETE', '/permissions/0');
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testDeleteSuccess()
    {
        $permission = factory(Permission::class)->create();

        $res = $this->call('DELETE', "/permissions/{$permission->id}");
        $this->assertEquals(204, $res->getStatusCode());

        $exists = Permission::find($permission->id);
        $this->assertNull($exists);
    }

    public function testBrowseNotFound()
    {
        $res = $this->call('GET', '/permissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));
    }

    public function testBrowseFound()
    {
        $permissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $permissions[] = factory(Permission::class)->create();
        }

        $permissions = Permission::select('*')->orderBy('id', 'desc')->get();

        $res = $this->call('GET', '/permissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(count($permissions), count($results->entities));
        for ($i = 0; $i < 10; ++$i) {
            $this->assertEquals($permissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderWrongParams()
    {
        $permissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $permissions[] = factory(Permission::class)->create();
        }

        $permissions = Permission::select('*')->orderBy('id', 'desc')->get();

        //check order permission with emty params
        $res = $this->call('GET', '/permissions');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissions); ++$i) {
            $this->assertEquals($permissions[$i]->id, $results->entities[$i]->id);
        }

        // check order permissions with wrong params
        $res = $this->call('GET', '/permissions?sort=sort&direction=direction');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissions); ++$i) {
            $this->assertEquals($permissions[$i]->id, $results->entities[$i]->id);
        }

        // check order permission with the input doesn't has sort
        $res = $this->call('GET', '/permissions?direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissions); ++$i) {
            $this->assertEquals($permissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderRightParams()
    {
        $permissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $permissions[] = factory(Permission::class)->create(['name' => 'Name '.$i, 'display_name' => 'Display name '.$i]);
        }
        $permissionsID = Permission::select('*')->orderBy('id', 'desc')->get();
        $permissionsDisplayName = Permission::select('*')->orderBy('display_name', 'desc')->get();
        $permissionsName = Permission::select('*')->orderBy('name', 'desc')->get();

        // check order permissions with full input
        $res = $this->call('GET', '/permissions?sort=name&direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissionsName); ++$i) {
            $this->assertEquals($permissionsName[$i]->id, $results->entities[$i]->id);
        }

        $res = $this->call('GET', '/permissions?sort=name&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissionsName); ++$i) {
            $this->assertEquals($permissionsName[9 - $i]->id, $results->entities[$i]->id);
        }

        // check order permission with other fields
        $res = $this->call('GET', '/permissions?sort=display_name');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($permissionsDisplayName); ++$i) {
            $this->assertEquals($permissionsDisplayName[$i]->id, $results->entities[$i]->id);
        }

        // check order permissions with equals value of order field, permission is sorted follow id field with desc
        $permissions = [];
        for ($i = 0; $i < 10; ++$i) {
            if(in_array($i, [2,4,6])) {
                $permissions[] = factory(Permission::class)->create(['display_name' => 'A']);
            }
            $permissions[] = factory(Permission::class)->create();
        }

        $permissions1 = Permission::where('display_name', '=', 'A')->orderBy('id', 'desc')->get();
        $permissions2 = Permission::where('display_name', '<>', 'A')->orderBy('order', 'asc')->get();
        $permissions = array_merge((array) $permissions1, (array) $permissions2);

        $res = $this->call('GET', '/permissions?sort=display_name&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 1; $i < count($permissions); ++$i) {
            $this->assertEquals($permissions[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithPagination()
    {
        $permissions = [];
        for ($i = 0; $i < 10; ++$i) {
            $permissions[] = factory(Permission::class)->create();
        }

        // 5 items first
        $res = $this->call('GET', '/permissions?limit=5');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($permissions[9 - $i]->id, $results->entities[$i]->id);
        }

        // 5 items next
        $nextLink = '/permissions?limit=5&page=2';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($permissions[4 - $i]->id, $results->entities[$i]->id);
        }

        // over list
        $nextLink = '/permissions?limit=5&page=3';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));
    }
}
