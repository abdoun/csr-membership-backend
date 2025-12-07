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
    }

    private function getAdminToken(): string
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        
        if (!$admin) {
            throw new \Exception('Admin user not found. Ensure migrations ran.');
        }

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin',
                'password' => 'admin',
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        return $response['token'] ?? null;
    }

    public function testGetUsersAsAdmin(): void
    {
        $token = $this->getAdminToken();
        $this->client->request('GET', '/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetUsersAsBasicUser(): void
    {
        // Create a basic user first
        $user = new User();
        $user->setName('Basic User');
        $username = 'basic_test_' . uniqid();
        $user->setUsername($username);
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);
        
        // Manually set bcrypt password for testing
        $user->setPassword('$2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW'); // bcrypt('admin')
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login as basic user
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => 'admin',
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'];

        // Try to access admin endpoint
        $this->client->request('GET', '/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUserAsAdmin(): void
    {
        $token = $this->getAdminToken();
        $username = 'new_user_' . uniqid();
        
        $this->client->request(
            'POST', 
            '/api/users', 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
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
        $token = $this->getAdminToken();
        
        // Create a user to update
        $user = new User();
        $user->setName('To Be Updated');
        $username = 'update_test_' . uniqid();
        $user->setUsername($username);
        $user->setPassword('$2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW'); // bcrypt('admin')
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
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['name' => 'Updated Name'])
        );
        $this->assertResponseIsSuccessful();
        
        $this->entityManager->clear(); // Clear cache to fetch fresh data
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertEquals('Updated Name', $updatedUser->getName());

        // 2. User updates themselves
        // Get token for this user
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => 'admin',
            ])
        );
        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userToken = $loginResponse['token'];

        $this->client->request(
            'PUT', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken],
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
        $otherUser->setPassword('$2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW'); // bcrypt('admin')
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
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken],
            json_encode(['name' => 'Hacker Update'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteUser(): void
    {
        $token = $this->getAdminToken();

        // Create a user to delete
        $user = new User();
        $user->setName('To Be Deleted');
        $username = 'delete_test_' . uniqid();
        $user->setUsername($username);
        $user->setPassword('$2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW'); // bcrypt('admin')
        $user->setLevel(UserLevel::BASIC);
        $user->setActive(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();

        // 1. Non-admin tries to delete (forbidden)
        // Get token for this user
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => 'admin',
            ])
        );
        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userToken = $loginResponse['token'];

        $this->client->request(
            'DELETE', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $userToken]
        );
        $this->assertResponseStatusCodeSame(403);

        // 2. Admin deletes user
        $this->client->request(
            'DELETE', 
            '/api/users/' . $userId, 
            [], 
            [], 
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame(204);

        $this->entityManager->clear();
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser);
    }
}
