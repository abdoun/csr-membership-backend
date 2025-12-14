<?php
// src/Controller/TestController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestController
{
    #[Route('/api/test-jwt', methods: ['GET'])]
    public function testJwt(): JsonResponse
    {
        $user = $this->getUser(); // JWT authenticated user
        return new JsonResponse([
            'username' => $user ? $user->getUserIdentifier() : null,
            'roles' => $user ? $user->getRoles() : []
        ]);
    }

    #[Route('/api/test-exception', methods: ['GET'])]
    public function testException(): JsonResponse
    {
        throw new \Exception("This is a test exception to check prod.log");
    }
}
