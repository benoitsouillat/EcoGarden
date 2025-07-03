<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/conseil', name: 'api_conseil_')]
final class ConseilController extends AbstractController
{
    public function __construct(
        public readonly EntityManagerInterface $manager,
        public readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator,
    ){}

    #[IsGranted('ROLE_USER', message: "Vous devez d'abord vous connecter")]
    #[Route('', name: 'conseils', methods: ['GET'])]
    public function getAllConseils(): JsonResponse
    {
        $conseils = $this->manager->getRepository(Conseil::class)->findAll();
        $json = $this->serializer->serialize($conseils, 'json', ['groups' => 'getConseils']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[IsGranted('ROLE_Admin', message: "Vous devez être administrateur pour ajouter un conseil.")]
    #[Route('', name: 'add', methods: ['POST'])]
    public function addConseil(Request $request, UrlGeneratorInterface $urlGenerator, Security $security): JsonResponse {
        $conseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json');
        $errors = $this->validator->validate($conseil);

        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $user = $security->getUser();
        $conseil->setUser($user);
        $this->manager->persist($conseil);
        $this->manager->flush();
        $json = $this->serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        $location = $urlGenerator->generate('api_conseil_add', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($json, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /*#[IsGranted('ROLE_USER', message: "Vous devez d'abord vous connecter")]*/
    #[Route('/{month}', name: 'conseil_show', methods: ['GET'])]
    public function getConseilsByMonth(Request $request): JsonResponse {
        $month = $request->get('month');
        $conseils = $this->manager->getRepository(Conseil::class)->findAllByMonth($month);
        $json = $this->serializer->serialize($conseils, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

/*    #[IsGranted('ROLE_USER', message: "Vous devez d'abord vous connecter")]
    #[Route('/{id}', name: 'conseil_show', methods: ['GET'])]
    public function getConseil(Conseil $conseil): JsonResponse {
        $json = $this->serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }*/

    #[IsGranted('ROLE_ADMIN', message: "Vous devez être administrateur pour modifier ce conseil.")]
    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function editConseil(Conseil $conseil, Request $request): JsonResponse {
        $updatedConseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $conseil]);
        $errors = $this->validator->validate($conseil);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $this->manager->persist($updatedConseil);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN', message: "Vous devez être administrateur pour supprimer ce conseil.")]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteConseil(Conseil $conseil): JsonResponse {
        $this->manager->remove($conseil);
        $this->manager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
