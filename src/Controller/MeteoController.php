<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/meteo', name: 'app_meteo_')]
final class MeteoController extends AbstractController {

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly TagAwareCacheInterface $cache
    ){}

    #[Route('', name: 'user')]
    public function userMeteo(Security $security, HttpClientInterface $httpClient): JsonResponse {
        $user = $security->getUser();
        $idCache = "getMeteo-" . $user->getId();
        $this->cache->invalidateTags(["meteoCache" . $user->getId()]);
        $weatherData = $this->cache->get($idCache, function (ItemInterface $item) use ($user, $httpClient) {
            $apiKey = $this->params->get('weatherApiKey');
            $response = $httpClient->request('GET', sprintf("https://api.openweathermap.org/data/2.5/weather?q=%s,fr&appid=%s&units=metric&lang=fr", $user->getPostalCode(), $apiKey));
            if ($response->getStatusCode() === 404) {
                throw new HttpException(404, "Aucune ville n'a été trouvée avec votre code postal");
            }
            $response = json_decode($response->getContent());
            $item->tag('meteoCache' . $user->getId());
            $item->expiresAfter(3600);
            return $this->setWeatherData($response);
        });
        return new JsonResponse($weatherData, Response::HTTP_OK);
    }

    #[Route('/{city}', name: 'city')]
    public function cityMeteo(Request $request, HttpClientInterface $httpClient): JsonResponse {
        $city = $request->get('city');
        $idCache = "getMeteo-" . $city;
        $weatherData = $this->cache->get($idCache, function (ItemInterface $item) use ($city, $httpClient) {
            $apiKey = $this->params->get('weatherApiKey');
            $response =  $httpClient->request('GET', sprintf("https://api.openweathermap.org/data/2.5/weather?q=%s,fr&appid=%s", $city, $apiKey));
            if ($response->getStatusCode() === 404) {
                throw new HttpException(404, "Cette ville n'a pas été trouvée");
            }
            $response = json_decode($response->getContent());
            $item->tag('meteoCache' . $city);
            $item->expiresAfter(3600);
            return $this->setWeatherData($response);
        });
        return new JsonResponse($weatherData, Response::HTTP_OK);
    }

    private function setWeatherData(\stdClass $data): array {
        return [
            'weather' => [
                'main' => $data->weather[0]->main,
                'description' => $data->weather[0]->description,
            ],
            'main' => [
                'humidity' => $data->main->humidity,
                'temp' => $data->main->temp,
                'pressure' => $data->main->pressure,
            ]
        ];
    }
}
