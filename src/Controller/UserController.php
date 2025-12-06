<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserLevel;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $requester = $this->getRequester($request);
        if (!$requester || $requester->getLevel() !== UserLevel::ADMIN) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $users = $this->userRepository->findAll();
        $data = $this->serializer->serialize($users, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, Request $request): JsonResponse
    {
        $requester = $this->getRequester($request);
        if (!$requester || $requester->getLevel() !== UserLevel::ADMIN) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($user, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $requester = $this->getRequester($request);
        if (!$requester || $requester->getLevel() !== UserLevel::ADMIN) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setName($data['name'] ?? null);
        $user->setUsername($data['username'] ?? null);
        
        if (!empty($data['password'])) {
            $user->setPassword(md5($data['password']));
        }
        
        if (isset($data['level'])) {
            try {
                $user->setLevel(UserLevel::from($data['level']));
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid level'], Response::HTTP_BAD_REQUEST);
            }
        } else {
             $user->setLevel(UserLevel::BASIC);
        }

        $user->setActive($data['active'] ?? false);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($user, 'json');

        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $requester = $this->getRequester($request);
        if (!$requester) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Access Control Logic
        if ($requester->getLevel() !== UserLevel::ADMIN) {
            // Basic/Advanced can only update themselves
            if ($requester->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        $data = json_decode($request->getContent(), true);

        // Admin can update everything
        if ($requester->getLevel() === UserLevel::ADMIN) {
            if (isset($data['name'])) {
                $user->setName($data['name']);
            }
            if (isset($data['username'])) {
                $user->setUsername($data['username']);
            }
            if (isset($data['level'])) {
                 try {
                    $user->setLevel(UserLevel::from($data['level']));
                } catch (\ValueError $e) {
                    return $this->json(['error' => 'Invalid level'], Response::HTTP_BAD_REQUEST);
                }
            }
            if (isset($data['active'])) {
                $user->setActive($data['active']);
            }
        }

        // Everyone (who is allowed here) can update password
        // If non-admin is here, we already checked they are updating themselves
        if (isset($data['password'])) {
            $user->setPassword(md5($data['password']));
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($user, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $requester = $this->getRequester($request);
        if (!$requester || $requester->getLevel() !== UserLevel::ADMIN) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getRequester(Request $request): ?User
    {
        // SIMULATION: In a real app, this would come from the Security Token
        // For this task, we assume the client sends 'X-Requester-Id' header
        $requesterId = $request->headers->get('X-Requester-Id');
        if (!$requesterId) {
            return null;
        }
        return $this->userRepository->find($requesterId);
    }
}
