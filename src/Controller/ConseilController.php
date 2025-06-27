<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conseil', name: 'api_conseil_')]
final class ConseilController extends AbstractController
{
    public function __construct(
        public readonly ConseilRepository $repository,
        public readonly UserRepository $userRepository,
        public readonly EntityManagerInterface $manager,
        public readonly SerializerInterface $serializer, //Event Dispatcher ??
    ){}

    #[Route('', name: 'conseils', methods: ['GET'])]
    public function getAllConseils(): JsonResponse
    {
        $conseils = $this->repository->findAll();
        $json = $this->serializer->serialize($conseils, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'conseil_show', methods: ['GET'])]
    public function getConseil(Request $request): JsonResponse
    {
        $conseil = $this->repository->find($request->get('id'));
        $json = $this->serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addConseil(Request $request, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $conseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json');
        $errors = $validator->validate($conseil);

        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }
        $user = $request->get('userId') ? $this->userRepository->find($request->get('userId')) : null;
        $conseil->setUser($user);
        $this->manager->persist($conseil);
        $this->manager->flush();
        $json = $this->serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        $location = $urlGenerator->generate('api_conseil_add', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($json, Response::HTTP_CREATED, ['Location' => $location], true);

    }
    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function editConseil(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $conseil = $this->repository->find($request->get('id'));
        $updatedConseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $conseil]);
        $user = $request->get('userId') ? $this->userRepository->find($request->get('userId')) : null;
        $conseil->setUser($user);
        $this->manager->persist($updatedConseil);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteConseil(Request $request): JsonResponse
    {
        $conseil = $this->repository->find($request->get('id'));
        $this->manager->remove($conseil);
        $this->manager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
