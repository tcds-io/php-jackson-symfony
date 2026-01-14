<?php

use App\Services\AuthTokenService;
use Psr\Container\ContainerInterface;
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
    'mappers' => [
        App\Domain\Foo::class => [],
        App\Queries\InvoiceQuery::class => [],
    ],
    'params' => static function (ContainerInterface $container) {
        $authService = $container->get(AuthTokenService::class);

        return $authService->getClaims();
    },
];
