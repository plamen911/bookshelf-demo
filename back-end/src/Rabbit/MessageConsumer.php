<?php

declare(strict_types=1);

namespace App\Rabbit;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class MessageConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorRepository $authorRepository,
        private readonly BookRepository   $bookRepository
    )
    {
    }

    /** @throws \Doctrine\DBAL\Exception */
    public function execute(AMQPMessage $msg): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->beginTransaction();

        try {
            $message = json_decode($msg->body, true);

            $title = $message['title'];
            $authorName = $message['author'];
            $pages = $message['pages'];
            $releaseDate = DateTimeImmutable::createFromFormat('d-m-Y', $message['releaseDate']);

            $author = $this->authorRepository->findOneBy(['name' => $authorName]);
            if (!$author) {
                $author = new Author();
                $author->setName($authorName);

                $this->authorRepository->save($author);
            }

            $book = new Book();
            $book->setTitle($title);
            $book->setAuthor($author);
            $book->setPages($pages);
            $book->setReleaseDate($releaseDate);

            $this->bookRepository->save($book, true);

            $connection->commit();

            echo 'Received a message from ' . $message['title'] . ' @ ' . $releaseDate->format('Y-m-d') . PHP_EOL;

        } catch (Exception $ex) {
            $connection->rollBack();
            echo $ex->getMessage() . PHP_EOL;
        }
    }
}
