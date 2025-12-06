<?php

namespace App\Tests\Application;

use App\Entity\User;
use App\Enum\UserLevel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
            
        // Clean up database before each test (optional, but good for isolation)
        // For simplicity in this environment, we might rely on the fact that we are using a persistent DB
        // In a real CI, we would use a separate test DB.
        // Let's just create a unique user for testing to avoid collisions.
    }

    private function getAdminId(): string
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        if (!$admin) {
            // Fallback if migration didn't run or was cleared, though it should be there
            $admin = new User();
            $admin->setName('Admin User');
            $admin->setUsername('admin');
            $admin->setPassword('21232f297a57a5a743894a0e4a801fc3');
            $admin->setLevel(UserLevel::ADMIN);
            $admin->setActive(true);
            $this->entityManager->persist($admin);
            $this->entityManager->flush();
        }
        return (string) $admin->getId();
    }

    public function testGetUsersAsAdmin(): void
    {
        $adminId = $this->getAdminId();
        $this->client->request('GET', '/api/users', [], [], ['HTTP_X_Requester_Id' => $adminId]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetUsersAsBasicUser(): void
    {
        // Create a basic user first
        $user = new User();
        $user->setName('Basic User');
        $user->setUsername('basic_test_' . uniqid());
        $user->setPassword(md5('password'));
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $userId = $user->getId();

        $this->client->request('GET', '/api/users', [], [], ['HTTP_X_Requester_Id' => (string)$userId]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUserAsAdmin(): void
    {
        $adminId = $this->getAdminId();
        $username = 'new_user_' . uniqid();
        
        $this->client->request(
            'POST', 
            '/api/users', 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Requester_Id' => $adminId],
            json_encode([
                'name' => 'New User',
                'username' => $username,
                'password' => 'password',
                'level' => 'basic',
                'active' => true
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        
        // Verify user exists
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->assertNotNull($user);
        $this->assertEquals('basic', $user->getLevel()->value);
    }

    public function testUpdateUser(): void
    {
        $adminId = $this->getAdminId();
        
        // Create a user to update
        $user = new User();
        $user->setName('To Be Updated');
        $user->setUsername('update_test_' . uniqid());
        $user->setPassword(md5('password'));
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();

        // 1. Admin updates user
        $this->client->request(
            'PUT', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Requester_Id' => $adminId],
            json_encode(['name' => 'Updated Name'])
        );
        $this->assertResponseIsSuccessful();
        
        $this->entityManager->clear(); // Clear cache to fetch fresh data
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertEquals('Updated Name', $updatedUser->getName());

        // 2. User updates themselves
        $this->client->request(
            'PUT', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Requester_Id' => (string)$userId],
            json_encode(['name' => 'Self Updated'])
        );
        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertEquals('Self Updated', $updatedUser->getName());

        // 3. User tries to update someone else (forbidden)
        $otherUser = new User();
        $otherUser->setName('Other User');
        $otherUser->setUsername('other_' . uniqid());
        $otherUser->setPassword(md5('password'));
        $otherUser->setLevel(UserLevel::BASIC);
        $otherUser->setActive(true);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        $otherUserId = $otherUser->getId();

        $this->client->request(
            'PUT', 
            '/api/users/' . $otherUserId, 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Requester_Id' => (string)$userId],
            json_encode(['name' => 'Hacker Update'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteUser(): void
    {
        $adminId = $this->getAdminId();

        // Create a user to delete
        $user = new User();
        $user->setName('To Be Deleted');
        $user->setUsername('delete_test_' . uniqid());
        $user->setPassword(md5('password'));
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();

        // 1. Non-admin tries to delete (forbidden)
        $this->client->request(
            'DELETE', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['HTTP_X_Requester_Id' => (string)$userId]
        );
        $this->assertResponseStatusCodeSame(403);

        // 2. Admin deletes user
        $this->client->request(
            'DELETE', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['HTTP_X_Requester_Id' => $adminId]
        );
        $this->assertResponseStatusCodeSame(204);

        $this->entityManager->clear();
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser);
    }
}
