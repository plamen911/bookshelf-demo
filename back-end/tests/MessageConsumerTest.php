<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Book;
use App\Rabbit\MessageConsumer;
use Doctrine\Persistence\ObjectManager;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageConsumerTest extends KernelTestCase
{
    private ObjectManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /** @throws Exception */
    public function test_message_is_stored_successfully_in_database(): void
    {
        $faker = \Faker\Factory::create();

        $bookRepository = $this->entityManager->getRepository(Book::class);

        $data = [
            'title' => implode(' ', $faker->words),
            'author' => $faker->firstName . ' ' . $faker->lastName,
            'pages' => $faker->numberBetween(50, 1000),
            'releaseDate' => \Faker\Provider\DateTime::dateTimeBetween('-99 years', 'now')->format('d-m-Y')
        ];

        $message = new AMQPMessage(json_encode($data), [
            'priority' => 0,
            'delivery_mode' => 2,
        ]);

        $this->assertCount(0, $bookRepository->findAll());

        /** @var MessageConsumer $messageConsumer */
        $messageConsumer = static::getContainer()->get('message_service');

        $messageConsumer->execute($message);

        $this->assertCount(1, $bookRepository->findAll());

        $book = $bookRepository->findOneBy(['title' => $data['title']]);

        $this->assertSame($data['title'], $book->getTitle());
        $this->assertSame($data['pages'], $book->getPages());
    }
}
