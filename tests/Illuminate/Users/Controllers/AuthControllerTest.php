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
        $this->assertEquals('Invalid Credentials.', $results->message);

        // user not found
        $res = $this->call('POST', '/auth/login', [
            'email' => 'nouser@example.com',
            'password' => '123456',
        ]);
        $this->assertEquals(401, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Invalid Credentials.', $results->message);

        // wrong password
        $res = $this->call('POST', '/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'abcdef',
        ]);
        $this->assertEquals(401, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Invalid Credentials.', $results->message);
    }

    public function testLoginSuccess()
    {
        $res = $this->call('POST', '/auth/login', [
            'email' => 'admin@example.com',
            'password' => '123456',
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertNotNull($results->entities[0]->token);

        $this->assertEquals('admin@example.com', Auth::user()->email);
    }

    public function testLogout()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $this->assertEquals('admin@example.com', Auth::user()->email);

        $res = $this->call('POST', '/auth/logout', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(204, $res->getStatusCode());
        $this->assertNull(Auth::user());

        $res = $this->call('POST', '/auth/logout', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(401, $res->getStatusCode());
    }
}
