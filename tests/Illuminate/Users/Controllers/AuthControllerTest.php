<?php

class AuthControllerTest extends TestCase
{
    public function testLoginFailure()
    {
        // not send credentials
        $res = $this->call('POST', '/auth/login');
        $this->assertEquals(401, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Invalid Credentials.', $results->error);

        // user not found
        $res = $this->call('POST', '/auth/login', [
            'email' => 'nouser@example.com',
            'password' => '123456',
        ]);
        $this->assertEquals(401, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Invalid Credentials.', $results->error);

        $user = factory(App\User::class)->create([
            'password'  => bcrypt('123456'),
        ]);

        // wrong password
        $res = $this->call('POST', '/auth/login', [
            'email' => $user->email,
            'password' => 'abcdef',
        ]);
        $this->assertEquals(401, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Invalid Credentials.', $results->error);
    }

    public function testLoginSuccess()
    {
        $user = factory(App\User::class)->create([
            'password'  => bcrypt('123456'),
        ]);

        $res = $this->call('POST', '/auth/login', [
            'email' => $user->email,
            'password' => '123456',
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertNotNull($results->entities[0]->token);

        $this->assertEquals($user->id, \Auth::user()->id);
    }

    public function testAuth()
    {
        $user = factory(App\User::class)->create([
            'password'  => bcrypt('123456'),
        ]);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('GET', '/me', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
    }
}
