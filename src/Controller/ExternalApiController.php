<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use OpenApi\Annotations as OA;


class ExternalApiController extends AbstractController
{
    /**
     * @OA\Tag(name="Externals")
     * 
     * This method call the root https://api.github.com/repos/symfony/symfony-docs
     * recovers the data and transmits it as is.
     *
     * For more informations  http client:
     * https://symfony.com/doc/current/http_client.html
     *
     * @param HttpClientInterface $httpClient
     * @return JsonResponse
     */
    #[Route('/api/external/getSfDoc', name: 'external_api', methods: 'GET')]
            public function getSymfonyDoc(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient->request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );
        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }
}
