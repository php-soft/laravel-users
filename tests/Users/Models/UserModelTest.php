<?php

class UserModelTest extends TestCase
{
    public function testStatusUser()
    {
        $user = new App\User();

        // check set status is active email
        $activeEmail = $user->activeEmail(0);
        $this->assertEquals(1, $activeEmail);

        // check set status is block
        $block = $user->block(0);
        $this->assertEquals(2, $block);

        // check set status non active email
        $nonActiveEmail = $user->nonActiveEmail(1);
        $this->assertEquals(0, $nonActiveEmail);

        // check set status non block
        $nonBlock = $user->nonBlock(2);
        $this->assertEquals(0, $nonBlock);

        // check status active email is true
        $activeEmail = $user->isActiveEmail(1);
        $this->assertEquals(true, $activeEmail);

        // check status active email is wrong
        $activeEmail = $user->isActiveEmail(0);
        $this->assertEquals(false, $activeEmail);

        // check status block is true
        $block = $user->isBlock(2);
        $this->assertEquals(true, $block);

        // check status block is wrong
        $block = $user->isBlock(1);
        $this->assertEquals(false, $block);
    }
}
