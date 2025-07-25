<?php

namespace App\Controller;

use App\Entity\Conseil;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route(
    '/conseil',
    name: 'api_conseil_'
)]
final class ConseilController extends AbstractController
{
    public function __construct(
        public readonly EntityManagerInterface $manager,
        public readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator,
        public readonly TagAwareCacheInterface $cache
    ){}

    #[Route(
        '',
        name: 'all',
        methods: ['GET']
    )]
    public function getAllConseils(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $repo = $this->manager->getRepository(Conseil::class);
        $idCache = "getAllConseils-" . $page . "-" . $limit;

        $json = $this->cache->get($idCache, function (ItemInterface $item) use ($repo, $page, $limit) {
            $month = (int)(new DateTime())->format('m');
            $item->tag('conseilsCache');
            $item->expiresAfter(600);
            $conseils = $repo->findAllWithPagination($month, $page, $limit);

            return $this->serializer->serialize($conseils, 'json', ['groups' => 'getConseils']);
        });

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{month}', name: 'show', requirements: ['id'=>'\d+'], methods: ['GET'])]
    public function getConseilsByMonth(int $month): JsonResponse {
        /*$month = $request->get('month');*/
        if ($month > 12 || $month < 1) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "Veuillez consulter un mois valide");
        }
        $conseils = $this->manager->getRepository(Conseil::class)->findAllByMonth($month);
        $json = $this->serializer->serialize($conseils, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[IsGranted('ROLE_ADMIN', message: "Vous devez être administrateur pour ajouter un conseil.")]
    #[Route('', name: 'add', methods: ['POST'])]
    public function addConseil(Request $request, UrlGeneratorInterface $urlGenerator, Security $security): JsonResponse {
        $conseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json');
        $errors = $this->validator->validate($conseil);

        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $user = $security->getUser();
        $conseil->setUser($user);
        $this->cache->invalidateTags(['conseilsCache']);
        $this->manager->persist($conseil);
        $this->manager->flush();
        $json = $this->serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        $location = $urlGenerator->generate('api_conseil_add', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($json, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[IsGranted('ROLE_ADMIN', message: "Vous devez être administrateur pour modifier ce conseil.")]
    #[Route('/{id}', name: 'edit', requirements: ['id'=>'\d+'], methods: ['PUT'],)]
    public function editConseil(Conseil $conseil, Request $request): JsonResponse {
        $updatedConseil = $this->serializer->deserialize($request->getContent(), Conseil::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $conseil]);
        $errors = $this->validator->validate($updatedConseil);
        if ($errors->count() > 0) {
            throw new ValidationFailedException("400", $errors);
        }
        $this->cache->invalidateTags(['conseilsCache']);
        $this->manager->persist($updatedConseil);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN', message: "Vous devez être administrateur pour supprimer ce conseil.")]
    #[Route('/{id}', name: 'delete', requirements: ['id'=>'\d+'], methods: ['DELETE'])]
    public function deleteConseil(Conseil $conseil): JsonResponse {
        $this->cache->invalidateTags(['conseilsCache']);
        $this->manager->remove($conseil);
        $this->manager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
