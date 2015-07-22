<?php

class UserControllerTest extends TestCase
{
    public function testGetAuthenticatedUserNotSendToken()
    {
        $res = $this->call('GET', '/me');
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Token not provided.', $results->error);
    }

    public function testGetAuthenticatedUser()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('GET', '/me', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Administrator', $results->entities[0]->name);
    }
}
