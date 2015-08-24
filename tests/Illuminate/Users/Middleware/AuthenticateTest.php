<?php 

use PhpSoft\Illuminate\Users\Middleware\Authenticate;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateTest extends TestCase
{
    public function testTokenHasExpired()
    {
        // Check token is expired
        $request = Mockery::mock();
        JWTAuth::shouldReceive('setRequest')->once()->andReturn($request);
        $request->shouldReceive('getToken')->once()->andReturn(true);
        JWTAuth::shouldReceive('authenticate')->once()->andThrow(new Tymon\JWTAuth\Exceptions\TokenExpiredException('tymon.jwt.expired', 404));

        $res = $this->call('POST', '/auth/logout');
        $this->assertEquals(404, $res->getStatusCode());
        $result = json_decode($res->getContent());
        $this->assertEquals('Token has expired.', $result->message);
        $this->assertEquals('error', $result->status);
        $this->assertEquals('authenticate', $result->type);
    }

    public function testUserNotFound()
    {
        // Check user not found
        $request = Mockery::mock();
        JWTAuth::shouldReceive('setRequest')->once()->andReturn($request);
        $request->shouldReceive('getToken')->once()->andReturn(true);
        JWTAuth::shouldReceive('authenticate')->once()->andReturn(false);

        $res = $this->call('POST', '/auth/logout');
        $result = json_decode($res->getContent());
        $this->assertEquals(404, $res->getStatusCode());
        $this->assertEquals('User not found.', $result->message);
        $this->assertEquals('error', $result->status);
        $this->assertEquals('authenticate', $result->type);
    }
}