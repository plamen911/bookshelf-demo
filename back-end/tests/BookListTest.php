<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Author;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\Persistence\ObjectManager;
use Faker\Provider\DateTime;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BookListTest extends ApiTestCase
{
    private Client $client;

    private ObjectManager $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->client = static::createClient();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function test_book_list_data_is_formatted_correctly(): void
    {
        $faker = \Faker\Factory::create();

        /** @var AuthorRepository $authorRepository */
        $authorRepository = $this->entityManager->getRepository(Author::class);

        /** @var BookRepository $bookRepository */
        $bookRepository = $this->entityManager->getRepository(Book::class);

        $author1 = new Author();
        $author1->setName($faker->firstName . ' ' . $faker->lastName);

        $authorRepository->save($author1, true);

        $book1 = new Book();
        $book1->setTitle(implode(' ', $faker->words));
        $book1->setAuthor($author1);
        $book1->setPages($faker->numberBetween(50, 1000));
        $book1->setReleaseDate(DateTime::dateTimeBetween('-99 years', 'now'));

        $bookRepository->save($book1, true);

        $author2 = new Author();
        $author2->setName($faker->firstName . ' ' . $faker->lastName);

        $authorRepository->save($author2, true);

        $book2 = new Book();
        $book2->setTitle(implode(' ', $faker->words));
        $book2->setAuthor($author2);
        $book2->setPages($faker->numberBetween(50, 1000));
        $book2->setReleaseDate(DateTime::dateTimeBetween('-3 years', 'now'));

        $bookRepository->save($book2, true);

        $this->client->request('GET', '/books', [
            'headers' => ['Accept' => 'application/json']
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            'data' => [
                [
                    'title' => $book2->getTitle(),
                    'author' => $book2->getAuthor()->getName(),
                    'pages' => $book2->getPages(),
                    'releaseDate' => $book2->getReleaseDate()->format('Y-m-d'),
                ], [
                    'title' => $book1->getTitle(),
                    'author' => $book1->getAuthor()->getName(),
                    'pages' => $book1->getPages(),
                    'releaseDate' => $book1->getReleaseDate()->format('Y-m-d'),
                ]
            ]
        ]);
    }
}
