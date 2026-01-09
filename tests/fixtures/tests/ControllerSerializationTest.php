<?php

namespace App\Tests;

use fixtures\src\Controller\FooBarController;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ControllerSerializationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    #[Test]
    public function controller_post_inject_param(): void
    {
        /**
         * @see FooBarController::read
         */
        $this->client->request('POST', '/controller/10', [
            'a' => 'something',
            'b' => 'something else',
            'type' => 'AAA',
        ]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            <<<JSON
            {
              "id": 10,
              "a": "something",
              "b": "something else",
              "type": "AAA"
            }
            JSON,
            $response->getContent(),
        );
    }

    #[Test]
    public function controller_invalid_inject_param(): void
    {
        /**
         * @see FooBarController::read
         */
        $this->client->request('POST', '/controller/10', [
            'a' => 'something',
            'b' => 'something else',
            'type' => 'YYY',
        ]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            <<<JSON
            {
              "message": "Unable to parse value at .type",
              "expected": ["AAA", "BBB"],
              "given": "string"
            }
            JSON,
            $response->getContent(),
        );
    }

    #[Test]
    public function controller_post_list_return_list(): void
    {
        /**
         * @see FooBarController::list
         */
        $this->client->request('POST', '/controller', [
            [
                'id' => 10,
                'a' => 'aaa',
                'b' => 'list aaa',
                'type' => 'AAA',
            ],
            [
                'id' => 11,
                'a' => 'bbb',
                'b' => 'list bbb',
                'type' => 'BBB',
            ],
        ]);

        $response = $this->client->getResponse();

        $this->assertJsonStringEqualsJsonString(
            <<<JSON
            [
                {
                  "id": 10,
                  "a": "aaa",
                  "b": "list aaa",
                  "type": "AAA"
                },
                {
                  "id": 11,
                  "a": "bbb",
                  "b": "list bbb",
                  "type": "BBB"
                }
            ]
            JSON,
            $response->getContent(),
        );
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function custom_params(): void
    {
        /**
         * @see FooBarController::invoices
         */
        $this->client->request('POST', '/invoices/165?customer=Tcds.Io');

        $response = $this->client->getResponse();

        $this->assertJsonStringEqualsJsonString(
            <<<JSON
            {
              "id": 165,
              "userId": 150,
              "customer": "Tcds.Io"
            }
            JSON,
            $response->getContent(),
        );
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
