<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'author', methods:['GET'])]
    public function getAllAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $nPage  = $request->get('page', 1);
        $nLimit = $request->get('limit', 3);

        $nIdCache = "getAllAuthors-".$nPage."-".$nLimit;

        $jsonAuthorList    = $cache->get($nIdCache, function(ItemInterface $item) use ($authorRepository, $nPage, $nLimit, $serializer){
            $item->tag('authorsCache');
            $authorList = $authorRepository->findAllWithPagination($nPage, $nLimit);
            return $serializer->serialize($authorList,'json', ['groups' => 'getAuthors']);
        });

        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'detailAuthor', methods:['GET'])]
    public function getOneAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getAuthors']);
        $jsonAuthor   = $serializer->serialize($author,'json', $context);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function deleteOneAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['authorsCache']);
        $em->remove($author);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors', name: 'createAuthor', methods:['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function createAuthor(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $author   = $serializer->deserialize($request->getContent(), Author::class, 'json');
        $errors = $validator->validate($author);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $em->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author,'json', ['groups' => 'getAuthors']);
        $location   = $urlGenerator->generate('detailAuthor', ['id' => $author->getid()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/authors/{id}', name: 'updateAuthor', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function updateAuthor(Request $request, Author $currentAuthor, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $errors = $validator->validate($currentAuthor);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $author   = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
        
        $em->persist($author);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
