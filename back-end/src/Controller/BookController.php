<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use Doctrine\DBAL\Exception;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    /** @throws Exception */
    #[Route('/books', name: 'app_list_books', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $books = $bookRepository->findBooksWithAuthors();

        $data = $serializer->serialize(['data' => $books], 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
