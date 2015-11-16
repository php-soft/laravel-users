<?php

use PhpSoft\Users\Models\Role;

class RoleControllerTest extends TestCase
{
    public function testCreateValidateFailure()
    {
        $res = $this->call('POST', '/roles', []);
        $this->assertEquals(400, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name field is required.', $results->errors->name[0]);

        $res = $this->call('POST', '/roles', [
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
        $res = $this->call('POST', '/roles', [
            'name'         => 'Create post',
            'display_name' => 'Post article.'
        ]);

        $this->assertEquals(201, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('entities', $results);
        $this->assertInternalType('array', $results->entities);
        $this->assertEquals('Create post', $results->entities[0]->name);
        $this->assertEquals(null, $results->entities[0]->description);
    }

    public function testReadNotFound()
    {
        $res = $this->call('GET', '/roles/0');

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testReadFound()
    {
        $role = factory(Role::class)->create();

        $res = $this->call('GET', '/roles/' . $role->id);

        $this->assertEquals(200, $res->getStatusCode());

        $results = json_decode($res->getContent());
        $this->assertObjectHasAttribute('entities', $results);
        $this->assertInternalType('array', $results->entities);
        $this->assertEquals($role->name, $results->entities[0]->name);
        $this->assertEquals($role->display_name, $results->entities[0]->display_name);
        $this->assertEquals($role->description, $results->entities[0]->description);
    }

    public function testUpdateNotExists()
    {
        $res = $this->call('PATCH', '/roles/0', [
            'name' => 'Create post',
        ]);
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testUpdateValidateFailure()
    {
        $role = factory(Role::class)->create();
        $role1 = factory(Role::class)->create();

        $res = $this->call('PATCH', '/roles/' . $role1->id, [
            'name' => $role->name,
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The name has already been taken.', $results->errors->name[0]);

        $res = $this->call('PATCH', '/roles/' . $role1->id, [
            'name'         => '',
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('The name field is required.', $results->errors->name[0]);
    }

    public function testUpdateNothingChange()
    {
        $role = factory(Role::class)->create();

        $res = $this->call('PATCH', '/roles/' . $role->id);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals($role->name, $results->entities[0]->name);
        $this->assertEquals($role->display_name, $results->entities[0]->display_name);
        $this->assertEquals($role->description, $results->entities[0]->description);
    }

    public function testUpdateWithNewInformation()
    {
        $role = factory(Role::class)->create();

        $res = $this->call('PATCH', '/roles/' . $role->id, [
            'name'         => 'create-post',
            'display_name' => 'Create post',
            'description'  => 'Create article',
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('create-post', $results->entities[0]->name);
        $this->assertEquals('Create post', $results->entities[0]->display_name);
        $this->assertEquals('Create article', $results->entities[0]->description);
    }

    public function testDeleteNotFound()
    {
        $res = $this->call('DELETE', '/roles/0');
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testDeleteSuccess()
    {
        $role = factory(Role::class)->create();

        $res = $this->call('DELETE', '/roles/'.$role->id);
        $this->assertEquals(204, $res->getStatusCode());

        $exists = Role::find($role->id);
        $this->assertNull($exists);
    }

    public function testBrowseNotFound()
    {
        $res = $this->call('GET', '/roles');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));
    }

    public function testBrowseFound()
    {
        $roles = [];
        for ($i = 0; $i < 10; ++$i) {
            $roles[] = factory(Role::class)->create();
        }

        $roles = Role::select('*')->orderBy('id', 'desc')->get();

        $res = $this->call('GET', '/roles');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(count($roles), count($results->entities));
        for ($i = 0; $i < 10; ++$i) {
            $this->assertEquals($roles[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderWrongParams()
    {
        $roles = [];
        for ($i = 0; $i < 10; ++$i) {
            $roles[] = factory(Role::class)->create();
        }

        $roles = Role::select('*')->orderBy('id', 'desc')->get();

        //check order roles with emty params
        $res = $this->call('GET', '/roles');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($roles); ++$i) {
            $this->assertEquals($roles[$i]->id, $results->entities[$i]->id);
        }

        // check order roles with wrong params
        $res = $this->call('GET', '/roles?sort=sort&direction=direction');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($roles); ++$i) {
            $this->assertEquals($roles[$i]->id, $results->entities[$i]->id);
        }

        // check order roles with the input doesn't has sort
        $res = $this->call('GET', '/roles?direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($roles); ++$i) {
            $this->assertEquals($roles[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderRightParams()
    {
        $roles = [];
        for ($i = 0; $i < 10; ++$i) {
            $roles[] = factory(Role::class)->create(['name' => 'admin '.$i, 'display_name' => 'Administrator '.$i]);
        }
        $rolesID = Role::select('*')->orderBy('id', 'desc')->get();
        $rolesDisplayName = Role::select('*')->orderBy('display_name', 'asc')->get();
        $rolesName = Role::select('*')->orderBy('name', 'desc')->get();

        // check order roles with full input
        $res = $this->call('GET', '/roles?sort=name&direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($rolesName); ++$i) {
            $this->assertEquals($rolesName[$i]->id, $results->entities[$i]->id);
        }
        $res = $this->call('GET', '/roles?sort=display_name&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($results->entities); ++$i) {
            $this->assertEquals($rolesDisplayName[$i]->id, $results->entities[$i]->id);
        }

        // check order roles with equals value of order field, roles is sorted follow id field with desc
        $roles = [];
        for ($i = 0; $i < 10; ++$i) {
            if(in_array($i, [2,4,6])) {
                $roles[] = factory(Role::class)->create(['display_name' => 'Administrator']);
            }
            $roles[] = factory(Role::class)->create();
        }

        $roles1 = Role::where('display_name', '=', 'Administrator')->orderBy('id', 'desc')->get();
        $roles2 = Role::where('display_name', '<>', 'Administrator')->orderBy('order', 'asc')->get();
        $roles = array_merge((array) $roles1, (array) $roles2);

        $res = $this->call('GET', '/roles?sort=display_name&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 1; $i < count($roles); ++$i) {
            $this->assertEquals($roles[$i]->id, $results->entities[$i]->id);
        }
    }

    public function testBrowseWithPagination()
    {
        $roles = [];
        for ($i = 0; $i < 10; ++$i) {
            $roles[] = factory(Role::class)->create();
        }

        // 5 items first
        $res = $this->call('GET', '/roles?limit=5');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($roles[9 - $i]->id, $results->entities[$i]->id);
        }

        // 5 items next
        $nextLink = '/roles?limit=5&page=2';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($roles[4 - $i]->id, $results->entities[$i]->id);
        }

        // over list
        $nextLink = '/roles?limit=5&page=3';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));
    }
}
