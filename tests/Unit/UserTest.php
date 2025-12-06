<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Enum\UserLevel;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserEntity(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setUsername('testuser');
        $user->setPassword('hashed_password');
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);

        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertEquals(UserLevel::BASIC, $user->getLevel());
        $this->assertTrue($user->isActive());
    }
}
