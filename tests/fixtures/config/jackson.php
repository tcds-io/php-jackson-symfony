<?php

use App\Services\AuthTokenService;
use Psr\Container\ContainerInterface as Container;
use Tcds\Io\Jackson\ObjectMapper;

/**
 * @returns array{
 *     mappers: array<class-string, array{
 *         reader?: callable(mixed $value, string $type, ObjectMapper $mapper, array $path): mixed,
 *         writer?: callable(mixed $data, string $type, ObjectMapper $mapper, array $path): mixed,
 *     }>,
 *     params?: callable(Container $container, ObjectMapper $mapper): array
 * }
 */
return [
    'mappers' => [
        App\Domain\Foo::class => [],
        App\Queries\InvoiceQuery::class => [],
    ],
    'params' => static function (Container $container) {
        $authService = $container->get(AuthTokenService::class);

        return $authService->getClaims();
    },
];
