<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        public readonly EntityManagerInterface $manager,
        private readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator,
    )
    {}

    #[Route('/user', name: 'api_user_create', methods: ['POST'])]
    public function makeUser(Request $request): JsonResponse {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        if ($this->manager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
            return new JsonResponse($this->serializer->serialize(['error' => 'Cet utilisateur existe déjà'], 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $this->manager->persist($user);
        $this->manager->flush();
        $json = $this->serializer->serialize($user, 'json');
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);

    }
}
