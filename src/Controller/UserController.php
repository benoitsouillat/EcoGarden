<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user', name: 'api_user_')]
final class UserController extends AbstractController
{
    public function __construct(
        public readonly EntityManagerInterface $manager,
        private readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator,
    )
    {}

    #[Route(
        '',
        name: 'create',
        methods: ['POST']
    )]
    public function makeUser(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        if ($this->manager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
            return new JsonResponse(
                $this->serializer->serialize(['error' => 'Cet utilisateur existe déjà'], 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }
        $user->setPassword($hasher->hashPassword($user, $user->getPassword()));
        $user->setRoles(['ROLE_USER']);
        $this->manager->persist($user);
        $this->manager->flush();

        $json = $this->serializer->serialize($user, 'json');
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[IsGranted(
        'ROLE_ADMIN',
        message: "Seuls les administrateurs peuvent mettre à jour les utilisateurs."
    )]
    #[Route(
        '/{id}',
        name: 'update',
        requirements: ['id'=>'\d+'],
        methods: ['PUT']
    )]
    public function updateUser(User $user, Request $request): JsonResponse
    {
        $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $errors = $this->validator->validate($updatedUser);
        if ($errors->count() > 0) {
            throw new ValidationFailedException("400", $errors);
        }
        $this->manager->persist($updatedUser);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted(
        'ROLE_ADMIN',
        message: "Seuls les administrateurs peuvent mettre à jour les utilisateurs."
    )]
    #[Route(
        '{id}',
        name: 'delete',
        requirements: ['id'=>'\d+'],
        methods: ['DELETE']
    )]
    public function deleteUser(User $user): JsonResponse
    {
        $this->manager->remove($user);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
