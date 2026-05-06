<?php

use Psr\Container\ContainerInterface;
use Tcds\Io\Jackson\Node\Reader;
use Tcds\Io\Jackson\Node\StaticReader;
use Tcds\Io\Jackson\Node\StaticWriter;
use Tcds\Io\Jackson\Node\Writer;
use Tcds\Io\Jackson\ObjectMapper;

/**
 * @returns array{
 *     errors?: array{
 *         request?: Closure(Tcds\Io\Jackson\Exception\UnableToParseValue $e): Symfony\Component\HttpFoundation\Response
 *     },
 *     mappers: array<class-string, array{
 *         reader?: Reader<mixed>|StaticReader<mixed>|Closure(mixed $data, string $type, ObjectMapper $mapper, list<string> $path): mixed,
 *         writer?: Writer<mixed>|StaticWriter<mixed>|Closure(mixed $data, string $type, ObjectMapper $mapper, list<string> $path): mixed,
 *     }>,
 *     params?: callable(ContainerInterface $container, ObjectMapper $mapper): array
 * }
 */
return [
    'errors' => [
        // 'request' => fn(UnableToParseValue $e) => new JsonResponse([...], Response::HTTP_UNPROCESSABLE_ENTITY),
    ],
    'mappers' => [
        // 'class-string' => [
        //    'reader' => fn(mixed $data) => new class-string($data[...], $data[...]),
        //    'writer' => fn(class-string $data) => [...],
        //],
    ],
    'params' => fn() => [
        // 'userId' => $container->get(Auth::class)->user->id
    ],
];
