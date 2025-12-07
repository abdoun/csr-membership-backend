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

    public function testAdminUserRoles(): void
    {
        $user = new User();
        $user->setLevel(UserLevel::ADMIN);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);
    }

    public function testAdvancedUserRoles(): void
    {
        $user = new User();
        $user->setLevel(UserLevel::ADVANCED);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADVANCED', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);
    }

    public function testBasicUserRoles(): void
    {
        $user = new User();
        $user->setLevel(UserLevel::BASIC);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setUsername('testuser');

        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPassword('secret');

        // Should not throw exception
        $user->eraseCredentials();
        $this->assertTrue(true);
    }
}
