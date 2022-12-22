<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookDto;
use App\Rabbit\MessagingProducer;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookController extends AbstractController
{
    /**
     * @param HttpClientInterface $client
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/books', name: 'app_list_books', methods: ['GET'])]
    public function index(HttpClientInterface $client): Response
    {
        try {
            // Forward this request to BACK-END and return unmodified response.
            $response = $client->request(
                'GET',
                'https://localhost:8001/books'
            );

            return new Response(
                $response->getContent(),
                $response->getStatusCode(),
                $response->getHeaders()
            );

        } catch (Exception $ex) {
            return new JsonResponse(['error' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/books', name: 'app_store_book', methods: ['POST'])]
    public function store(
        Request             $request,
        SerializerInterface $serializer,
        ValidatorInterface  $validator,
        MessagingProducer   $messagingProducer
    ): JsonResponse
    {
        try {
            /** @var BookDto $bookDto */
            $bookDto = $serializer->deserialize($request->getContent(), BookDto::class, 'json');

            // validate request
            $errors = $validator->validate($bookDto);

            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[$violation->getPropertyPath()][] = $violation->getMessage();
                }

                return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check release date
            $errors = [];
            try {
                $releaseDate = DateTimeImmutable::createFromFormat('d-m-Y', $bookDto->getReleaseDate());

                if ($releaseDate->format('d-m-Y') !== $bookDto->getReleaseDate()) {
                    throw new Exception('Invalid date.');
                }

                $releaseDateYear = abs($releaseDate->diff(new DateTimeImmutable('now'))->y);
                if ($releaseDateYear > 100) {
                    throw new Exception('Release date should be +/- 100 years');
                }
            } catch (Exception $ex) {
                $errors['releaseDate'][] = $ex->getMessage();
            }

            if (count($errors) > 0) {
                return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // submit to RabbitMQ
            $messagingProducer->publish($serializer->serialize($bookDto, 'json'));

            return $this->json(['status' => 'Sent!'], Response::HTTP_CREATED);

        } catch (Exception $ex) {
            return $this->json(['error' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
