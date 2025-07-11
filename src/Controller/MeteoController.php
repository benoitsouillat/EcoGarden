<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/meteo', name: 'app_meteo_')]
final class MeteoController extends AbstractController {

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly TagAwareCacheInterface $cache
    ){}

    #[Route('', name: 'user')]
    public function userMeteo(Security $security): JsonResponse {
        $user = $security->getUser();
        $idCache = "getMeteo-" . $user->getId();
        $weatherData = $this->cache->get($idCache, function (ItemInterface $item) use ($user) {
            $apiKey = $this->params->get('weatherApiKey');
            $response = file_get_contents("https://api.openweathermap.org/data/2.5/weather?zip=".$user->getPostalCode().",fr&appid=".$apiKey);
            $item->tag('meteoCache' . $user->getId());
            $item->expiresAfter(60);
            return json_decode($response, true);
        });
        return new JsonResponse($weatherData, Response::HTTP_OK);
    }

    #[Route('/{city}', name: 'city')]
    public function cityMeteo(Request $request): JsonResponse {
        $city = $request->get('city');
        $weatherData = $this->cache->get($city, function (ItemInterface $item) use ($city) {
        $apiKey = $this->params->get('weatherApiKey');
            $response = file_get_contents(sprintf("https://api.openweathermap.org/data/2.5/weather?q=%s,fr&appid=%s", $city, $apiKey));
            $item->tag('meteoCache' . $city);
            $item->expiresAfter(60);
            return json_decode($response, true);
        });
        return new JsonResponse($weatherData, Response::HTTP_OK);
    }
}
