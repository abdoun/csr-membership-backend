<?php

namespace App\Security;

use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Entity\User;

class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private string $jwtSecretKey,
        private int $tokenTtl,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && ($request->getContentTypeFormat() === 'json' || 
                str_contains($request->headers->get('Content-Type', ''), 'application/json'));
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (!$username || !$password) {
            throw new AuthenticationException('Missing username or password');
        }

        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('User is not active');
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [new PasswordUpgradeBadge($password)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();

        // Generate JWT token
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->tokenTtl,
            'sub' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        $jwt = JWT::encode($payload, $this->jwtSecretKey, 'HS256');

        return new JsonResponse([
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'name' => $user->getName(),
                'level' => $user->getLevel()->value,
                'roles' => $user->getRoles(),
            ],
        ], Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
