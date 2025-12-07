<?php

namespace App\Tests\Application;

use App\Entity\User;
use App\Enum\UserLevel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
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

        // Send login request
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

    /**
     * @test
     */
    public function testLoginWithValidCredentials(): void
    {
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

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertEquals('admin', $response['user']['username']);
        $this->assertContains('ROLE_ADMIN', $response['user']['roles']);
    }

    /**
     * @test
     */
    public function testLoginWithInvalidPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin',
                'password' => 'wrongpassword',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * @test
     */
    public function testLoginWithNonexistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'nonexistent',
                'password' => 'password',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * @test
     */
    public function testLoginWithMissingUsername(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'admin',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     */
    public function testLoginWithMissingPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     */
    public function testJwtTokenIsValidForProtectedEndpoints(): void
    {
        $token = $this->getAdminToken();
        
        $this->client->request(
            'GET',
            '/api/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($response);
        $this->assertGreaterThan(0, count($response));
        $this->assertEquals('admin', $response[0]['username']);
    }

    /**
     * @test
     */
    public function testAccessProtectedEndpointWithoutToken(): void
    {
        $this->client->request('GET', '/api/users');

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('Not privileged', $response['message']);
    }

    /**
     * @test
     */
    public function testAccessProtectedEndpointWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.here']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     */
    public function testLogoutWithValidToken(): void
    {
        $token = $this->getAdminToken();

        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('logged out', $response['message']);
    }

    /**
     * @test
     */
    public function testLogoutWithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     */
    public function testTokenContainsCorrectClaims(): void
    {
        $response = json_decode($this->makeLoginRequest('admin', 'admin'), true);
        $token = $response['token'];

        // JWT structure: header.payload.signature
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT should have 3 parts');

        // Decode payload (base64 decode with padding)
        $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=')), true);

        $this->assertArrayHasKey('iat', $payload, 'Token should have iat (issued at)');
        $this->assertArrayHasKey('exp', $payload, 'Token should have exp (expiration)');
        $this->assertArrayHasKey('sub', $payload, 'Token should have sub (subject/username)');
        $this->assertArrayHasKey('roles', $payload, 'Token should have roles');
        
        $this->assertEquals('admin', $payload['sub']);
        $this->assertContains('ROLE_ADMIN', $payload['roles']);
    }

    private function makeLoginRequest(string $username, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );

        return $this->client->getResponse()->getContent();
    }
}
