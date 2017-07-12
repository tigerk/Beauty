<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetuser()
    {
        $user = new User();
        $value = $user->getuser();
        $this->assertEquals($value[0]['user_id'], 'user@example.com');
    }
}