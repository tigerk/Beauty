<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetuser()
    {
        $userlist = \Beauty\Model\User::where("user_id","1")->getOne();

        $user = $userlist->toArray();

        $this->assertEquals($user['user_id'], 'user@example.com');
    }
}