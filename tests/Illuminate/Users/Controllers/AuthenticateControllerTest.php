<?php

class AuthenticateControllerTest extends TestCase
{
    public function testLogin()
    {
        $user = factory(App\User::class)->create([
            'password'  => bcrypt('123456'),
        ]);

        $res = $this->call('POST', '/auth/login', [
            'email' => $user->email,
            'password' => '123456',
        ]);

        $results = json_decode($res->getContent());
        $token = $results->token;

        $res = $this->call('GET', "/me?token={$token}", ['a'=>23]);
        // dump($res);
    }
}
