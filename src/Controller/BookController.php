<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'book', methods:['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList       = $bookRepository->findAll();
        $jsonBookList   = $serializer->serialize($bookList,'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detailBook', methods:['GET'])]
    public function getOneBook(Book $book, SerializerInterface $serializer): JsonResponse
    {
        $jsonBook   = $serializer->serialize($book,'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'deleteBook', methods:['DELETE'])]
    public function deleteOneBook(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books', name: 'createBook', methods:['POST'])]
    public function createBook(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $book   = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $em->persist($book);
        $em->flush();
        $jsonBook   = $serializer->serialize($book,'json', ['groups' => 'getBooks']);
        $location   = $urlGenerator->generate('detailBook', ['id' => $book->getid()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['Location' => $location], true);
    }
}
