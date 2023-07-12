<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'author', methods:['GET'])]
    public function getAllAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authorList       = $authorRepository->findAll();
        $jsonAuthorList   = $serializer->serialize($authorList,'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'detailAuthor', methods:['GET'])]
    public function getOneAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $jsonAuthor   = $serializer->serialize($author,'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods:['DELETE'])]
    public function deleteOneAuthor(Author $author, EntityManagerInterface $em, BookRepository $bookRepository): JsonResponse
    {
        //we must to delete book before 
        $aBooks = $author->getBooks()->toArray();
        foreach ($aBooks as $oBook) {
            $bookRepository->remove($oBook);
        }
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
