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
}
