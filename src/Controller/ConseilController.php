<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/conseil', name: 'api_conseil_')]
final class ConseilController extends AbstractController
{
    public function __construct(
        public readonly ConseilRepository $repository
    ){}

    #[Route('', name: 'conseils', methods: ['GET'])]
    public function getAllConseils(): JsonResponse
    {
        $conseils = $this->repository->findAll();

        return new JsonResponse([
            'conseils' => $conseils,
        ]);
    }
}
