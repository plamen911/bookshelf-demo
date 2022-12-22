<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BookTest extends ApiTestCase
{
    private Client $client;
    private array $authHeaders;

    private array $testData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->authHeaders = [
            'Accept' => 'application/json',
            'X-API-KEY' => '0123456789'
        ];

        $this->testData = [
            'title' => 'The Parent Agency',
            'author' => 'David Baddiel',
            'pages' => 59,
            'releaseDate' => '23-09-2004'
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_book_post_endpoint_is_protected(): void
    {
        $this->client->request('POST', '/books', [
            'json' => $this->testData,
            'headers' => ['X-API-KEY' => 'wrong-token']
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertJsonContains(['message' => 'Wrong API token provided']);

        $this->client->request('POST', '/books', [
            'json' => $this->testData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function test_book_post_endpoint_with_correct_data(): void
    {
        $this->client->request('POST', '/books', [
            'json' => $this->testData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->assertJsonContains([
            'status' => 'Sent!'
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function test_book_post_data_is_validated_correctly(): void
    {
        $postData = $this->testData;

        // testing empty title
        $postData['title'] = '';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'title' => [
                    'This value should not be blank.'
                ]
            ]
        ]);

        // testing title contains non-allowed symbols
        $postData['title'] = 'test@!%';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'title' => [
                    'Allowed symbols: azAz0-9.'
                ]
            ]
        ]);

        // testing title length is max 30 chars
        $postData['title'] = 'test123 test123 test123 test123 test123 test123 test123 test123 test123 test123 ';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'title' => [
                    'This value is too long. It should have 30 characters or less.'
                ]
            ]
        ]);

        // testing pages count is between 0 and 1000
        $postData = $this->testData;
        $postData['pages'] = 1001;

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'pages' => [
                    'Pages must be between 0 and 1000 to enter'
                ]
            ]
        ]);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_book_release_date(): void
    {
        // testing release date format (dd-mm-yyyy)
        $postData = $this->testData;
        $postData['releaseDate'] = '12/22/2022';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'releaseDate' => [
                    'Date format must be dd-mm-yyyy to enter'
                ]
            ]
        ]);

        // testing invalid release date
        $postData = $this->testData;
        $postData['releaseDate'] = '99-02-2022';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'releaseDate' => [
                    'Invalid date.'
                ]
            ]
        ]);

        // testing release date range
        $postData = $this->testData;
        $postData['releaseDate'] = '01-12-1900';

        $this->client->request('POST', '/books', [
            'json' => $postData,
            'headers' => $this->authHeaders
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertJsonContains([
            'errors' => [
                'releaseDate' => [
                    'Release date should be +/- 100 years'
                ]
            ]
        ]);
    }
}
