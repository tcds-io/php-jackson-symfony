<?php

use App\Services\AuthTokenService;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tcds\Io\Jackson\Exception\UnableToParseValue;
use Tcds\Io\Jackson\Node\Reader;
use Tcds\Io\Jackson\Node\StaticReader;
use Tcds\Io\Jackson\Node\StaticWriter;
use Tcds\Io\Jackson\Node\Writer;
use Tcds\Io\Jackson\ObjectMapper;

/**
 * @returns array{
 *     mappers: array<class-string, array{
 *         reader?: Reader<mixed>|StaticReader<mixed>|Closure(mixed $data, string $type, ObjectMapper $mapper, list<string> $path): mixed,
 *         writer?: Writer<mixed>|StaticWriter<mixed>|Closure(mixed $data, string $type, ObjectMapper $mapper, list<string> $path): mixed,
 *     }>,
 *     params?: callable(ContainerInterface $container, ObjectMapper $mapper): array
 * }
 */
return [
    'errors' => [
        'request' => fn(UnableToParseValue $e) => new JsonResponse([
            'error' => $e->getMessage(),
            'hint' => 'Check the request body format.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY),
    ],
    'mappers' => [
        App\Domain\Foo::class => [],
        App\Queries\InvoiceQuery::class => [],
    ],
    'params' => static function (ContainerInterface $container) {
        $authService = $container->get(AuthTokenService::class);

        return $authService->getClaims();
    },
];
