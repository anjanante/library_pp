<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Service\VersionningService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class BookController extends AbstractController
{
    /**
     * This method retrieves all the books.
     *
     * @OA\Response(
     *     response=200,
     *     description="Back to book list",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getBooks"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page you want to retrieve",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The number of elements to be retrieved",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Books")
     *
     * @param BookRepository $bookRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'book', methods:['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $nPage  = $request->get('page', 1);
        $nLimit = $request->get('limit', 3);

        $nIdCache = "getAllBooks-".$nPage."-".$nLimit;

        $jsonBookList    = $cache->get($nIdCache, function(ItemInterface $item) use ($bookRepository, $nPage, $nLimit, $serializer){
            $item->tag('booksCache');
            $bookList = $bookRepository->findAllWithPagination($nPage, $nLimit);
            $context = SerializationContext::create()->setGroups(['getBooks']);
            return $serializer->serialize($bookList,'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    /**
     * Get one book from id 
     * 
     * @OA\Tag(name="Books")
     *
     * @param Book $book
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/books/{id}', name: 'detailBook', methods:['GET'])]
    public function getOneBook(Book $book, SerializerInterface $serializer, VersionningService $versionningService): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getBooks']);
        $context->setVersion($versionningService->getVersion());
        $jsonBook   = $serializer->serialize($book,'json', $context);
        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    /**
     * Delete one book from id.
     * 
     * @OA\Tag(name="Books")
     *
     * @param Book $book
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse 
     */
    #[Route('/api/books/{id}', name: 'deleteBook', methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function deleteOneBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        $cache->invalidateTags(['booksCache']);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Example  of data : 
     * {
     *     "title": "My title",
     *     "coverText": "This is the history of a man", 
     *     "idAuthor": 5
     * }
     * 
     * @OA\Tag(name="Books")
     * 
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param UrlGeneratorInterface $urlGenerator
     * @param AuthorRepository $authorRepository
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'createBook', methods:['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function createBook(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $book   = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La request is invalid"); //for subscriber
        }

        //set author
        $content    = $request->toArray();
        $idAuthor   = $content['idAuthor'] ?? -1;
        $book->setAuthor($authorRepository->find($idAuthor));

        $em->persist($book);
        $em->flush();

        //empty the cache
        $cache->invalidateTags(['booksCache']);

        $context = SerializationContext::create()->setGroups(['getBooks']);
        $jsonBook   = $serializer->serialize($book,'json', $context);
        $location   = $urlGenerator->generate('detailBook', ['id' => $book->getid()], UrlGeneratorInterface::ABSOLUTE_PATH);
        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['Location' => $location], true);
    }
    
    /**
     * Example  of data : 
     * {
     *     "title": "My title",
     *     "coverText": "This is the history of a man", 
     *     "idAuthor": 5
     * }
     * 
     * @OA\Tag(name="Books")
     * 
     * @param Request $request
     * @param Book $currentBook
     * @param EntityManagerInterface $em
     * @param AuthorRepository $authorRepository
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/books/{id}', name: 'updateBook', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not the admin, sorry')]
    public function updateBook(Request $request, Book $currentBook, EntityManagerInterface $em, SerializerInterface $serializer,  AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $newBook   = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());

        $errors = $validator->validate($currentBook);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La request is invalid"); //for subscriber
        }

        //set author
        $content    = $request->toArray();
        $idAuthor   = $content['idAuthor'] ?? -1;
        $currentBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($currentBook);
        $em->flush();

        //empty the cache
        $cache->invalidateTags(['booksCache']);
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
